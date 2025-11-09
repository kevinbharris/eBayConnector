<?php

namespace KevinBHarris\EbayConnector\Listeners;

use KevinBHarris\EbayConnector\Services\ProductSyncService;
use Webkul\Product\Events\ProductUpdated;
use Illuminate\Support\Facades\Log;

class SyncProductOnUpdate
{
    protected ProductSyncService $syncService;

    public function __construct(ProductSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the event.
     */
    public function handle(ProductUpdated $event): void
    {
        if (!core()->getConfigData('online_merchants.ebay_connector.sync.auto_sync_products')) {
            return;
        }

        try {
            $this->syncService->syncToEbay($event->product);
        } catch (\Exception $e) {
            Log::error("Auto sync product on update failed: {$e->getMessage()}");
        }
    }
}
