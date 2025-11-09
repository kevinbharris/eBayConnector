# Configuration Migration Guide

## Overview

The eBay Connector package has been refactored to use Bagisto's standard system configuration approach instead of custom database tables and forms. This makes the package consistent with Bagisto/Webkul conventions and allows merchants to manage all settings through Bagisto's native Configuration interface.

## What Changed

### 1. New System Configuration File

A new `publishable/config/system.php` file has been created following Bagisto/Webkul conventions:

- **Location**: `publishable/config/system.php`
- **Structure**: Configuration grouped under `sales.carriers.ebayconnector`
- **Why "carriers"**: In Bagisto, the "Sales" section contains payment methods and shipping carriers. Using `sales.carriers` ensures the eBay Connector appears in the "Online Merchants" group, which shows first in the sales configuration section.

### 2. Configuration Groups

The system.php file organizes settings into logical groups:

1. **Main Settings** (`sales.carriers.ebayconnector`)
   - Enable/Disable connector
   - Environment (Sandbox/Production)
   - API credentials (Key, Secret, Dev ID, Cert ID)
   - OAuth Redirect URI

2. **Synchronization Settings** (`sales.carriers.ebayconnector.sync`)
   - Auto sync products
   - Auto sync orders
   - Sync interval

3. **Product Sync Settings** (`sales.carriers.ebayconnector.product_sync`)
   - Sync images, inventory, pricing, attributes
   - Default listing duration
   - Default dispatch time

4. **Order Sync Settings** (`sales.carriers.ebayconnector.order_sync`)
   - Sync order status
   - Sync tracking information
   - Auto-create customers
   - Default order status

5. **Logging Settings** (`sales.carriers.ebayconnector.logging`)
   - Enable/disable logging
   - Log retention days

### 3. Code Refactoring

All package code has been updated to use `core()->getConfigData()` instead of the old config approach:

#### Services
- **EbayApiClient**: Uses `core()->getConfigData('sales.carriers.ebayconnector.api_key')` etc.
- **ProductSyncService**: Uses `core()->getConfigData('sales.carriers.ebayconnector.logging.enabled')`
- **OrderSyncService**: Uses `core()->getConfigData('sales.carriers.ebayconnector.order_sync.default_order_status')`

#### Controllers
- **ConfigurationController**: Now redirects to Bagisto's core configuration page
- **LogController**: Uses `core()->getConfigData('sales.carriers.ebayconnector.logging.retention_days')`

#### Listeners
- **SyncProductOnCreate**: Uses `core()->getConfigData('sales.carriers.ebayconnector.sync.auto_sync_products')`
- **SyncProductOnUpdate**: Uses `core()->getConfigData('sales.carriers.ebayconnector.sync.auto_sync_products')`

### 4. Menu Changes

The menu structure has been updated:

**Before:**
- eBay Connector
  - Configuration
  - Product Sync
  - Order Sync
  - Sync Logs

**After:**
- eBay Connector (links to Bagisto Configuration)
  - Product Sync
  - Order Sync
  - Sync Logs

Configuration is now accessed through: **Settings → Configuration → Sales → Carriers → eBay Connector**

## How to Access Configuration

1. Log into Bagisto admin panel
2. Navigate to: **Settings → Configuration**
3. Click on **Sales** in the left sidebar
4. Scroll to find **eBay Connector** (will appear in the "Carriers" section)
5. Configure all settings through the standard Bagisto interface

## Benefits of This Approach

1. **Consistency**: Follows Bagisto/Webkul package conventions
2. **Multi-channel Support**: Configurations are channel-based (can be different per sales channel)
3. **Centralized Management**: All settings in one place with other Bagisto configurations
4. **No Custom Tables**: Uses Bagisto's `core_config` table instead of custom `ebay_configurations` table
5. **Standard UI**: Merchants familiar with Bagisto will know exactly where to find settings
6. **Validation**: Built-in Bagisto validation for configuration fields

## Migration Notes

If you were using the old custom configuration page:

1. The old custom configuration database table (`ebay_configurations`) is still present but no longer used
2. You'll need to reconfigure settings through the new Bagisto Configuration interface
3. The custom configuration view (`resources/views/admin/configuration/index.blade.php`) is no longer used but preserved for reference

## Technical Details

### Configuration Access Patterns

**Old Way (Deprecated):**
```php
config('ebayconnector.api_key')
config('ebayconnector.auto_sync.products')
```

**New Way (Standard Bagisto):**
```php
core()->getConfigData('sales.carriers.ebayconnector.api_key')
core()->getConfigData('sales.carriers.ebayconnector.sync.auto_sync_products')
```

### ServiceProvider Registration

The `EbayConnectorServiceProvider` now registers the system configuration:

```php
$this->mergeConfigFrom(
    dirname(__DIR__, 2) . '/publishable/config/system.php',
    'core'
);
```

This merges the package's system configuration into Bagisto's core configuration system.

### Field Types

The system.php uses standard Bagisto field types:
- `text`: Text input fields
- `password`: Password input fields (masked)
- `boolean`: Checkbox fields
- `select`: Dropdown fields with predefined options

Each field can have:
- `name`: Field identifier
- `title`: Translation key for field label
- `type`: Field type
- `validation`: Laravel validation rules
- `channel_based`: Whether the setting can differ per channel
- `locale_based`: Whether the setting can differ per locale
- `options`: Available options for select fields

## Troubleshooting

### Configuration Not Appearing

If the eBay Connector configuration doesn't appear:

1. Clear all caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

2. Ensure the service provider is registered in `composer.json`:
   ```json
   "extra": {
       "laravel": {
           "providers": [
               "KevinBHarris\\EbayConnector\\Providers\\EbayConnectorServiceProvider"
           ]
       }
   }
   ```

3. Run package discovery:
   ```bash
   composer dump-autoload
   php artisan package:discover
   ```

### Settings Not Saving

1. Check file permissions on the `storage` and `bootstrap/cache` directories
2. Ensure the `core_config` table exists in your database
3. Check Laravel logs in `storage/logs/laravel.log`

## Support

For issues or questions:
- GitHub: https://github.com/kevinbharris/ebayconnector/issues
- Email: kevin.b.harris.2015@gmail.com
