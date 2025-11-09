# Installation and Setup Guide

## eBay Connector for Bagisto 2.3.8

This guide provides detailed instructions for installing and configuring the eBay Connector package.

---

## Prerequisites

- **PHP**: 8.1 or higher
- **Laravel**: 10.x or 11.x  
- **Bagisto**: 2.3.8 or compatible version
- **eBay Developer Account** with API credentials

---

## Installation Methods

You can install this package in two ways:
1. **Via Composer** (recommended for production)
2. **Direct from GitHub** (for development or manual installation)

---

## Method 1: Installation via Composer (Packagist)

This is the recommended method for most users.

### Step 1: Install via Composer

```bash
composer require kevinbharris/ebayconnector
```

### Step 2: Publish Configuration Files

Publish the configuration file:
```bash
php artisan vendor:publish --tag=ebayconnector-config
```

Publish the views (optional, only if you want to customize):
```bash
php artisan vendor:publish --tag=ebayconnector-views
```

Publish the assets (CSS and icon fonts):
```bash
php artisan vendor:publish --tag=ebayconnector-assets
```

This will publish:
- Custom eBay icon font files to `public/vendor/ebayconnector/fonts/ebay/`
- Icon font CSS to `public/vendor/ebayconnector/css/ebay-icons.css`

**Note:** The custom eBay icon font provides a black and white eBay logo that appears in the Bagisto admin sidebar next to "eBay Connector".

### Step 3: Run Migrations

Execute the database migrations:
```bash
php artisan migrate
```

This will create the following tables:
- `ebay_configurations` - Stores configuration settings
- `ebay_product_mappings` - Maps Bagisto products to eBay items
- `ebay_order_mappings` - Maps eBay orders to Bagisto orders
- `ebay_sync_logs` - Logs all synchronization activities

### Step 4: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Method 2: Installation from GitHub (Manual)

If you've downloaded the package directly from GitHub or want to install it manually:

### Step 1: Download the Package

Option A - Clone the repository:
```bash
cd packages/kevinbharris
git clone https://github.com/kevinbharris/ebayconnector.git
```

Option B - Download ZIP:
1. Download the package from [GitHub](https://github.com/kevinbharris/ebayconnector)
2. Extract it to `packages/kevinbharris/ebayconnector` in your Bagisto installation

Your directory structure should look like:
```
your-bagisto-project/
├── app/
├── packages/
│   └── kevinbharris/
│       └── ebayconnector/
│           ├── src/
│           ├── resources/
│           ├── publishable/
│           ├── composer.json
│           └── ...
├── public/
├── vendor/
└── ...
```

### Step 2: Add Local Repository to Composer

Edit your project's `composer.json` file and add the package to the repositories section:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/kevinbharris/ebayconnector",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

### Step 3: Require the Package

Install the package from the local repository:

```bash
composer require kevinbharris/ebayconnector:@dev
```

**Note**: The `@dev` flag tells Composer to use the local development version.

### Step 4: Verify Installation

Check that the package is installed:

```bash
composer show kevinbharris/ebayconnector
```

### Step 5: Publish Configuration Files

Publish the configuration file:
```bash
php artisan vendor:publish --tag=ebayconnector-config
```

Publish the views (optional):
```bash
php artisan vendor:publish --tag=ebayconnector-views
```

Publish the assets:
```bash
php artisan vendor:publish --tag=ebayconnector-assets
```

### Step 6: Run Migrations

Execute the database migrations:
```bash
php artisan migrate
```

This will create the following tables:
- `ebay_configurations`
- `ebay_product_mappings`
- `ebay_order_mappings`
- `ebay_sync_logs`

### Step 7: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 8: Verify Package Registration

Check if the service provider is loaded:
```bash
php artisan package:discover
```

You should see `KevinBHarris\EbayConnector\Providers\EbayConnectorServiceProvider` in the list.

---

## Configuration

### Option 1: Via Admin Panel (Recommended)

1. Log in to Bagisto Admin Panel
2. Navigate to **eBay Connector** → **Configuration** in the sidebar
3. Fill in your eBay API credentials:
   - **Enable eBay Connector**: Check to activate
   - **Environment**: Select Sandbox or Production
   - **API Key (Client ID)**: Your eBay App ID
   - **API Secret (Client Secret)**: Your eBay Cert ID
   - **Developer ID**: Your eBay Dev ID (optional)
   - **Certificate ID**: Additional cert ID (optional)
4. Configure sync settings:
   - **Auto Sync Products**: Enable automatic product synchronization
   - **Auto Sync Orders**: Enable automatic order synchronization
   - **Sync Interval**: Set interval in minutes (default: 15)
5. Click **Test Connection** to verify credentials
6. Click **Save Configuration**

### Option 2: Via Environment Variables

Add the following to your `.env` file:

```env
# eBay Connector Settings
EBAY_CONNECTOR_ENABLED=true
EBAY_ENVIRONMENT=sandbox

# eBay API Credentials
EBAY_API_KEY=your-client-id-here
EBAY_API_SECRET=your-client-secret-here
EBAY_DEV_ID=your-dev-id-here
EBAY_CERT_ID=your-cert-id-here

# Auto Sync Settings
EBAY_AUTO_SYNC_PRODUCTS=true
EBAY_AUTO_SYNC_ORDERS=true
EBAY_SYNC_INTERVAL=15
```

---

## Getting eBay API Credentials

1. Go to [eBay Developer Portal](https://developer.ebay.com/)
2. Sign in or create an account
3. Navigate to **My Account** → **Keys**
4. Create a new keyset for your application
5. Note down:
   - **App ID (Client ID)** - Use as API Key
   - **Cert ID (Client Secret)** - Use as API Secret
   - **Dev ID** - Developer ID

---

## Admin Panel Access

After installation, you'll find the eBay Connector menu in the Bagisto admin sidebar:

```
eBay Connector
├── Configuration - Configure API credentials and settings
├── Product Sync - Manage product synchronization
├── Order Sync - Manage order synchronization
└── Sync Logs - View synchronization logs and history
```

---

## Usage Examples

### Syncing Products

**Via Admin Panel:**
1. Navigate to **eBay Connector** → **Product Sync**
2. Select products to sync
3. Click **Sync Selected** or **Sync All Products**

**Via CLI:**
```bash
# Sync all products
php artisan ebay:sync-products --all

# Sync specific products
php artisan ebay:sync-products --ids=1,2,3,4,5
```

### Syncing Orders

**Via Admin Panel:**
1. Navigate to **eBay Connector** → **Order Sync**
2. Click **Sync New Orders** to import recent orders

**Via CLI:**
```bash
# Sync new orders (last 24 hours)
php artisan ebay:sync-orders --new

# Sync specific order
php artisan ebay:sync-orders --id=ORDER_ID
```

### Viewing Logs

1. Navigate to **eBay Connector** → **Sync Logs**
2. Filter by type (Product/Order) or status (Success/Error/Pending)
3. Click **Clear Old Logs** to remove logs older than retention period

---

## Scheduled Tasks (Optional)

To enable automatic synchronization, add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync products every hour
    $schedule->command('ebay:sync-products --all')
        ->hourly()
        ->when(config('ebayconnector.auto_sync.products'));

    // Sync orders every 15 minutes
    $schedule->command('ebay:sync-orders --new')
        ->everyFifteenMinutes()
        ->when(config('ebayconnector.auto_sync.orders'));
}
```

Make sure your cron is configured:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Troubleshooting

### Menu Not Appearing in Admin Sidebar

If the "eBay Connector" menu doesn't appear in the Bagisto admin sidebar after installation:

**Step 1: Verify Service Provider Registration**
```bash
php artisan package:discover
```
You should see `KevinBHarris\EbayConnector\Providers\EbayConnectorServiceProvider` in the output.

If not listed, check `config/app.php` and verify the provider is in the `providers` array (Laravel should auto-discover it).

**Step 2: Clear All Caches**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

**Step 3: Publish Package Resources**
If not already done, publish the configuration files:
```bash
php artisan vendor:publish --tag=ebayconnector-config
php artisan vendor:publish --tag=ebayconnector-views
php artisan vendor:publish --tag=ebayconnector-assets
```

**Step 4: Check Laravel Logs**
Review `storage/logs/laravel.log` for any warnings or errors related to eBay Connector:
```bash
tail -f storage/logs/laravel.log | grep "eBay Connector"
```

If you see warnings about "Bagisto core binding not found", this indicates:
- Bagisto core may not be properly installed
- You may be running the package outside a Bagisto environment
- Service providers are loading in the wrong order

**Step 5: Verify Bagisto Installation**
Ensure Bagisto is properly installed and the `core` service is available:
```bash
php artisan tinker
>>> app()->bound('core')
```
This should return `true` in a properly configured Bagisto installation.

**Step 6: Check Admin User Permissions**
Ensure your admin user has the necessary ACL permissions:
1. Navigate to **Settings** → **Users** → **Roles**
2. Edit your admin role
3. Ensure "eBay Connector" permissions are checked
4. Save and log out/in again

**Step 7: Verify Database Tables**
Check if migrations ran successfully:
```bash
php artisan migrate:status
```
You should see these tables:
- `ebay_configurations`
- `ebay_product_mappings`
- `ebay_order_mappings`
- `ebay_sync_logs`

If missing, run:
```bash
php artisan migrate
```

### Connection Test Fails
- Verify API credentials are correct
- Check if environment (sandbox/production) matches your credentials
- Ensure your eBay app has the correct scopes enabled

### Products Not Syncing
- Check sync logs for errors
- Verify products have all required fields (SKU, name, price)
- Ensure products are active/enabled

### Orders Not Importing
- Verify OAuth token is valid
- Check API permissions for order access
- Review sync logs for specific errors

---

## Security Notes

- Never commit `.env` file with credentials
- Use environment variables for sensitive data
- Regularly rotate API credentials
- Monitor sync logs for unauthorized access attempts

---

## Support

For issues or questions:
- **GitHub Issues**: [kevinbharris/ebayconnector](https://github.com/kevinbharris/ebayconnector/issues)
- **Email**: kevin.b.harris.2015@gmail.com

---

## License

MIT License - See LICENSE file for details
