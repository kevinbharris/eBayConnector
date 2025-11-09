<?php

namespace KevinBHarris\EbayConnector\Services;

use KevinBHarris\EbayConnector\Models\EbayProductMapping;
use KevinBHarris\EbayConnector\Models\EbaySyncLog;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductSyncService
{
    protected EbayApiClient $apiClient;

    public function __construct(EbayApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync a product to eBay.
     */
    public function syncToEbay(Product $product): bool
    {
        try {
            $mapping = EbayProductMapping::firstOrCreate(
                ['product_id' => $product->id],
                ['status' => 'pending']
            );

            $itemData = $this->prepareProductData($product);

            if ($mapping->ebay_item_id) {
                // Update existing listing
                $result = $this->apiClient->updateListing($product->sku, $itemData);
            } else {
                // Create new listing
                $result = $this->apiClient->createListing($itemData);
            }

            if ($result) {
                $mapping->update([
                    'ebay_item_id' => $result['sku'] ?? $product->sku,
                    'ebay_listing_id' => $result['listingId'] ?? null,
                    'status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_data' => $result,
                ]);

                $this->logSync('product', 'sync', $product->id, 'success', 'Product synced successfully', $itemData, $result);
                return true;
            }

            $this->logSync('product', 'sync', $product->id, 'error', 'Failed to sync product', $itemData, $result);
            return false;
        } catch (\Exception $e) {
            Log::error("Product sync failed: {$e->getMessage()}", ['product_id' => $product->id]);
            $this->logSync('product', 'sync', $product->id, 'error', $e->getMessage());
            return false;
        }
    }

    /**
     * Sync products from eBay to Bagisto.
     */
    public function syncFromEbay(string $itemId): bool
    {
        try {
            $ebayItem = $this->apiClient->getItem($itemId);

            if (!$ebayItem) {
                return false;
            }

            // Find or create product
            $mapping = EbayProductMapping::where('ebay_item_id', $itemId)->first();
            
            if ($mapping && $mapping->product) {
                $product = $mapping->product;
                $this->updateProductFromEbay($product, $ebayItem);
            } else {
                $product = $this->createProductFromEbay($ebayItem);
                
                EbayProductMapping::create([
                    'product_id' => $product->id,
                    'ebay_item_id' => $itemId,
                    'status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_data' => $ebayItem,
                ]);
            }

            $this->logSync('product', 'import', $product->id, 'success', 'Product imported from eBay', null, $ebayItem);
            return true;
        } catch (\Exception $e) {
            Log::error("Product import from eBay failed: {$e->getMessage()}", ['item_id' => $itemId]);
            $this->logSync('product', 'import', null, 'error', $e->getMessage());
            return false;
        }
    }

    /**
     * Sync multiple products to eBay.
     */
    public function syncMultipleToEbay(array $productIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            
            if (!$product) {
                $results['failed']++;
                $results['errors'][] = "Product {$productId} not found";
                continue;
            }

            if ($this->syncToEbay($product)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to sync product {$productId}";
            }
        }

        return $results;
    }

    /**
     * Sync all products to eBay.
     */
    public function syncAllToEbay(): array
    {
        $products = Product::all();
        $productIds = $products->pluck('id')->toArray();
        
        return $this->syncMultipleToEbay($productIds);
    }

    /**
     * Update product inventory on eBay.
     */
    public function updateInventory(Product $product): bool
    {
        try {
            $mapping = EbayProductMapping::where('product_id', $product->id)->first();

            if (!$mapping || !$mapping->ebay_item_id) {
                return false;
            }

            $quantity = $product->inventories->sum('qty') ?? 0;
            $result = $this->apiClient->updateInventory($product->sku, $quantity);

            if ($result) {
                $mapping->update([
                    'last_synced_at' => now(),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Inventory update failed: {$e->getMessage()}", ['product_id' => $product->id]);
            return false;
        }
    }

    /**
     * Prepare product data for eBay.
     */
    protected function prepareProductData(Product $product): array
    {
        return [
            'sku' => $product->sku,
            'product' => [
                'title' => $product->name,
                'description' => $product->description ?? $product->short_description ?? '',
                'aspects' => $this->prepareProductAspects($product),
                'imageUrls' => $this->prepareProductImages($product),
            ],
            'condition' => 'NEW',
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => $product->inventories->sum('qty') ?? 0,
                ],
            ],
        ];
    }

    /**
     * Prepare product aspects/attributes.
     */
    protected function prepareProductAspects(Product $product): array
    {
        $aspects = [];
        
        // Add product attributes
        if ($product->attribute_family) {
            foreach ($product->attribute_values as $attributeValue) {
                // Determine the actual value from various value types
                $value = $attributeValue->text_value 
                    ?? $attributeValue->boolean_value 
                    ?? $attributeValue->integer_value 
                    ?? $attributeValue->float_value 
                    ?? $attributeValue->date_value 
                    ?? $attributeValue->datetime_value 
                    ?? $attributeValue->json_value;
                
                // Only add aspects with non-null and non-empty values
                if (!is_null($value) && $value !== '') {
                    $aspects[$attributeValue->attribute->name] = [$value];
                }
            }
        }

        return $aspects;
    }

    /**
     * Prepare product images.
     */
    protected function prepareProductImages(Product $product): array
    {
        $images = [];
        
        foreach ($product->images as $image) {
            $images[] = url('storage/' . $image->path);
        }

        return $images;
    }

    /**
     * Create product from eBay data.
     */
    protected function createProductFromEbay(array $ebayItem): Product
    {
        // This is a simplified version - actual implementation would need more details
        $productData = [
            'type' => 'simple',
            'sku' => $ebayItem['sku'] ?? 'ebay-' . $ebayItem['itemId'],
            'attribute_family_id' => 1, // Default attribute family
        ];

        return Product::create($productData);
    }

    /**
     * Update product from eBay data.
     */
    protected function updateProductFromEbay(Product $product, array $ebayItem): void
    {
        // Update product details from eBay
        // This is a simplified version
        $product->update([
            'name' => $ebayItem['title'] ?? $product->name,
            'description' => $ebayItem['description'] ?? $product->description,
        ]);
    }

    /**
     * Log sync activity.
     */
    protected function logSync(
        string $type,
        string $action,
        ?int $entityId,
        string $status,
        string $message = '',
        ?array $requestData = null,
        ?array $responseData = null
    ): void {
        if (!core()->getConfigData('online_merchants.ebay_connector.logging.enabled')) {
            return;
        }

        EbaySyncLog::create([
            'type' => $type,
            'action' => $action,
            'entity_id' => $entityId,
            'entity_type' => 'product',
            'status' => $status,
            'message' => $message,
            'request_data' => $requestData,
            'response_data' => $responseData,
        ]);
    }
}
