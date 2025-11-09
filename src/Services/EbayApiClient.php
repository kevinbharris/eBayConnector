<?php

namespace KevinBHarris\EbayConnector\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EbayApiClient
{
    protected Client $client;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $environment;
    protected ?string $accessToken = null;

    public function __construct(?string $apiKey = null, ?string $apiSecret = null, ?string $environment = 'sandbox')
    {
        $this->apiKey = $apiKey ?? core()->getConfigData('online_merchants.ebay_connector.api.api_key');
        $this->apiSecret = $apiSecret ?? core()->getConfigData('online_merchants.ebay_connector.api.api_secret');
        $this->environment = $environment ?? core()->getConfigData('online_merchants.ebay_connector.api.environment') ?? 'sandbox';
        
        $apiEndpoint = $this->environment === 'production' 
            ? config('ebayconnector.api_endpoints.production')
            : config('ebayconnector.api_endpoints.sandbox');
            
        $this->client = new Client([
            'base_uri' => $apiEndpoint,
            'timeout' => 30,
        ]);
    }

    /**
     * Get OAuth access token.
     */
    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // Try to get from cache
        $cachedToken = Cache::get('ebay_access_token');
        if ($cachedToken) {
            $this->accessToken = $cachedToken;
            return $this->accessToken;
        }

        // Generate new token
        try {
            $oauthEndpoint = $this->environment === 'production'
                ? config('ebayconnector.oauth_endpoints.production')
                : config('ebayconnector.oauth_endpoints.sandbox');
                
            $response = $this->client->post(
                $oauthEndpoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . base64_encode("{$this->apiKey}:{$this->apiSecret}"),
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'scope' => 'https://api.ebay.com/oauth/api_scope',
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
            $this->accessToken = $data['access_token'] ?? null;

            if ($this->accessToken) {
                $expiresIn = $data['expires_in'] ?? 7200;
                Cache::put('ebay_access_token', $this->accessToken, $expiresIn - 300); // 5 min buffer
            }

            return $this->accessToken;
        } catch (GuzzleException $e) {
            Log::error('eBay OAuth failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Make an API request.
     */
    public function request(string $method, string $endpoint, array $data = [], array $headers = []): ?array
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('No valid eBay access token available');
            return null;
        }

        try {
            $defaultHeaders = [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $options = [
                'headers' => array_merge($defaultHeaders, $headers),
            ];

            if (!empty($data)) {
                if (strtoupper($method) === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("eBay API request failed: {$e->getMessage()}", [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data,
            ]);
            return null;
        }
    }

    /**
     * Get item details from eBay.
     */
    public function getItem(string $itemId): ?array
    {
        return $this->request('GET', "/buy/browse/v1/item/{$itemId}");
    }

    /**
     * Create a listing on eBay.
     */
    public function createListing(array $itemData): ?array
    {
        return $this->request('POST', '/sell/inventory/v1/inventory_item', $itemData);
    }

    /**
     * Update a listing on eBay.
     */
    public function updateListing(string $sku, array $itemData): ?array
    {
        return $this->request('PUT', "/sell/inventory/v1/inventory_item/{$sku}", $itemData);
    }

    /**
     * Delete a listing from eBay.
     */
    public function deleteListing(string $sku): bool
    {
        $result = $this->request('DELETE', "/sell/inventory/v1/inventory_item/{$sku}");
        return $result !== null;
    }

    /**
     * Get orders from eBay.
     */
    public function getOrders(array $filters = []): ?array
    {
        return $this->request('GET', '/sell/fulfillment/v1/order', $filters);
    }

    /**
     * Get specific order details.
     */
    public function getOrder(string $orderId): ?array
    {
        return $this->request('GET', "/sell/fulfillment/v1/order/{$orderId}");
    }

    /**
     * Update inventory quantity.
     */
    public function updateInventory(string $sku, int $quantity): ?array
    {
        return $this->request('POST', "/sell/inventory/v1/inventory_item/{$sku}", [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => $quantity,
                ],
            ],
        ]);
    }

    /**
     * Test API connection.
     */
    public function testConnection(): bool
    {
        $token = $this->getAccessToken();
        return $token !== null;
    }
}
