<?php

namespace KevinBHarris\EbayConnector\Services;

use KevinBHarris\EbayConnector\Models\EbayOrderMapping;
use KevinBHarris\EbayConnector\Models\EbaySyncLog;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    protected EbayApiClient $apiClient;

    public function __construct(EbayApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync orders from eBay to Bagisto.
     */
    public function syncFromEbay(array $filters = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $ordersData = $this->apiClient->getOrders($filters);

            if (!$ordersData || empty($ordersData['orders'])) {
                return $results;
            }

            foreach ($ordersData['orders'] as $ebayOrder) {
                if ($this->importOrder($ebayOrder)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to import order {$ebayOrder['orderId']}";
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error("Order sync from eBay failed: {$e->getMessage()}");
            $results['errors'][] = $e->getMessage();
            return $results;
        }
    }

    /**
     * Import a single order from eBay.
     */
    public function importOrder(array $ebayOrder): bool
    {
        try {
            $ebayOrderId = $ebayOrder['orderId'];

            // Check if order already exists
            $mapping = EbayOrderMapping::where('ebay_order_id', $ebayOrderId)->first();

            if ($mapping && $mapping->order) {
                // Update existing order
                $this->updateOrderFromEbay($mapping->order, $ebayOrder);
                $mapping->update([
                    'last_synced_at' => now(),
                    'sync_data' => $ebayOrder,
                ]);
            } else {
                // Create new order
                $order = $this->createOrderFromEbay($ebayOrder);

                EbayOrderMapping::create([
                    'order_id' => $order->id,
                    'ebay_order_id' => $ebayOrderId,
                    'ebay_transaction_id' => $ebayOrder['lineItems'][0]['lineItemId'] ?? null,
                    'status' => 'synced',
                    'last_synced_at' => now(),
                    'sync_data' => $ebayOrder,
                ]);
            }

            $this->logSync('order', 'import', $mapping->order_id ?? $order->id ?? null, 'success', 'Order imported from eBay', null, $ebayOrder);
            return true;
        } catch (\Exception $e) {
            Log::error("Order import failed: {$e->getMessage()}", ['ebay_order' => $ebayOrder]);
            $this->logSync('order', 'import', null, 'error', $e->getMessage(), null, $ebayOrder);
            return false;
        }
    }

    /**
     * Sync specific order from eBay by order ID.
     */
    public function syncOrderById(string $ebayOrderId): bool
    {
        try {
            $ebayOrder = $this->apiClient->getOrder($ebayOrderId);

            if (!$ebayOrder) {
                return false;
            }

            return $this->importOrder($ebayOrder);
        } catch (\Exception $e) {
            Log::error("Order sync failed: {$e->getMessage()}", ['order_id' => $ebayOrderId]);
            return false;
        }
    }

    /**
     * Get new orders from eBay (last 24 hours).
     */
    public function syncNewOrders(): array
    {
        $filters = [
            'filter' => 'creationdate:[' . now()->subDay()->toIso8601String() . '..' . now()->toIso8601String() . ']',
        ];

        return $this->syncFromEbay($filters);
    }

    /**
     * Update order status on eBay.
     */
    public function updateOrderStatus(Order $order, string $status): bool
    {
        try {
            $mapping = EbayOrderMapping::where('order_id', $order->id)->first();

            if (!$mapping || !$mapping->ebay_order_id) {
                return false;
            }

            // Update order status on eBay
            // Note: This is a simplified version - actual eBay API might have different endpoints
            $result = $this->apiClient->request('POST', "/sell/fulfillment/v1/order/{$mapping->ebay_order_id}/issue_refund", [
                'status' => $status,
            ]);

            if ($result) {
                $mapping->update([
                    'last_synced_at' => now(),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Order status update failed: {$e->getMessage()}", ['order_id' => $order->id]);
            return false;
        }
    }

    /**
     * Create order from eBay data.
     */
    protected function createOrderFromEbay(array $ebayOrder): Order
    {
        // This is a simplified version - actual implementation would be more complex
        // and would need to handle customer creation, order items, etc.
        
        $orderData = [
            'increment_id' => 'EBAY-' . $ebayOrder['orderId'],
            'status' => core()->getConfigData('online_merchants.ebay_connector.order_sync.default_order_status') ?? 'pending',
            'channel_name' => 'eBay',
            'is_guest' => 0,
            'customer_email' => $ebayOrder['buyer']['buyerEmail'] ?? 'noreply@ebay.com',
            'customer_first_name' => $ebayOrder['buyer']['firstName'] ?? 'eBay',
            'customer_last_name' => $ebayOrder['buyer']['lastName'] ?? 'Customer',
            'base_currency_code' => $ebayOrder['pricingSummary']['total']['currency'] ?? 'USD',
            'channel_currency_code' => $ebayOrder['pricingSummary']['total']['currency'] ?? 'USD',
            'order_currency_code' => $ebayOrder['pricingSummary']['total']['currency'] ?? 'USD',
            'base_grand_total' => $ebayOrder['pricingSummary']['total']['value'] ?? 0,
            'grand_total' => $ebayOrder['pricingSummary']['total']['value'] ?? 0,
        ];

        return Order::create($orderData);
    }

    /**
     * Update order from eBay data.
     */
    protected function updateOrderFromEbay(Order $order, array $ebayOrder): void
    {
        // Update order details from eBay
        // This is a simplified version
        $updateData = [];

        if (isset($ebayOrder['orderFulfillmentStatus'])) {
            $updateData['status'] = $this->mapEbayStatusToBagisto($ebayOrder['orderFulfillmentStatus']);
        }

        if (!empty($updateData)) {
            $order->update($updateData);
        }
    }

    /**
     * Map eBay order status to Bagisto status.
     */
    protected function mapEbayStatusToBagisto(string $ebayStatus): string
    {
        $statusMap = [
            'FULFILLED' => 'completed',
            'IN_PROGRESS' => 'processing',
            'NOT_STARTED' => 'pending',
            'CANCELLED' => 'canceled',
        ];

        return $statusMap[$ebayStatus] ?? 'pending';
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
            'entity_type' => 'order',
            'status' => $status,
            'message' => $message,
            'request_data' => $requestData,
            'response_data' => $responseData,
        ]);
    }
}
