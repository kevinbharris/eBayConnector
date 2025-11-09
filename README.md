# eBay Connector for Bagisto

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Bagisto](https://img.shields.io/badge/Bagisto-2.3.8-blue.svg)](https://bagisto.com/)

A feature-rich connector that seamlessly synchronizes products and orders between Bagisto and eBay.

## Features

- **Automatic Product Sync**: Automatically synchronize products from Bagisto to eBay when they are created or updated
- **Manual Product Sync**: Sync selected products or all products on-demand through the admin panel
- **Order Synchronization**: Import orders from eBay to Bagisto automatically or manually
- **Admin Panel Integration**: Full-featured admin interface for configuration and management
- **Sync Logs**: Comprehensive logging system to track all synchronization activities
- **Real-time Inventory Updates**: Keep inventory levels in sync between platforms
- **OAuth Authentication**: Secure authentication using eBay's OAuth 2.0

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Bagisto 2.3.8 or compatible version
- eBay Developer Account with API credentials

## Installation

### Method 1: Via Composer (Recommended)

1. Install the package via Composer:

```bash
composer require kevinbharris/ebayconnector
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=ebayconnector-config
```

3. Publish the views (optional):

```bash
php artisan vendor:publish --tag=ebayconnector-views
```

4. Publish the assets:

```bash
php artisan vendor:publish --tag=ebayconnector-assets
```

5. Run the migrations:

```bash
php artisan migrate
```

### Method 2: Manual Installation from GitHub

If you've downloaded the package from GitHub:

1. Create a `packages/kevinbharris` directory in your Bagisto root
2. Clone or extract the package into `packages/kevinbharris/ebayconnector`
3. Add to your `composer.json`:

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

4. Install the package:

```bash
composer require kevinbharris/ebayconnector:@dev
```

5. Follow steps 2-5 from Method 1 above

**For detailed GitHub installation instructions, see [GITHUB_INSTALL.md](GITHUB_INSTALL.md)**

For complete installation guide, see [INSTALLATION.md](INSTALLATION.md).

## Configuration

### 1. eBay API Credentials

Obtain your eBay API credentials from the [eBay Developer Portal](https://developer.ebay.com/):
- Client ID (App ID)
- Client Secret (Cert ID)
- Developer ID

### 2. Configure via Admin Panel

1. Navigate to **Admin Panel → Settings → Configuration**
2. In the left sidebar, click on **Sales**
3. Scroll down to find **eBay Connector** in the Carriers section
4. Enter your eBay API credentials
5. Select the environment (Sandbox or Production)
6. Configure synchronization settings (auto-sync, intervals, etc.)
7. Configure product sync settings (images, inventory, pricing, etc.)
8. Configure order sync settings (status, tracking, customer creation)
9. Configure logging settings
10. Save the configuration

**Note**: The configuration is now fully integrated with Bagisto's standard configuration system and supports channel-based settings.

For more details on the configuration migration, see [CONFIGURATION_MIGRATION.md](CONFIGURATION_MIGRATION.md).

### 3. Environment Variables (Optional)

You can still use environment variables for default values. These are defined in `publishable/config/ebayconnector.php` and serve as fallbacks:

```env
EBAY_CONNECTOR_ENABLED=true
EBAY_ENVIRONMENT=sandbox
EBAY_API_KEY=your-client-id
EBAY_API_SECRET=your-client-secret
EBAY_DEV_ID=your-dev-id
EBAY_CERT_ID=your-cert-id
EBAY_AUTO_SYNC_PRODUCTS=true
EBAY_AUTO_SYNC_ORDERS=true
EBAY_SYNC_INTERVAL=15
```

## Usage

### Product Synchronization

#### Via Admin Panel

1. Navigate to **Admin Panel → eBay Connector → Products**
2. Select products you want to sync
3. Click "Sync Selected" or "Sync All Products"

#### Via Artisan Commands

Sync all products:
```bash
php artisan ebay:sync-products --all
```

Sync specific products:
```bash
php artisan ebay:sync-products --ids=1,2,3
```

### Order Synchronization

#### Via Admin Panel

1. Navigate to **Admin Panel → eBay Connector → Orders**
2. Click "Sync New Orders" to import recent orders from eBay

#### Via Artisan Commands

Sync new orders (last 24 hours):
```bash
php artisan ebay:sync-orders --new
```

Sync specific order:
```bash
php artisan ebay:sync-orders --id=ORDER_ID
```

### Viewing Sync Logs

Navigate to **Admin Panel → eBay Connector → Logs** to view all synchronization activities, errors, and details.

## Automatic Synchronization

Enable automatic synchronization in the configuration panel:
- **Auto Sync Products**: Products will be synced to eBay when created or updated
- **Auto Sync Orders**: Orders will be imported from eBay at the specified interval

## Package Structure

```
ebayconnector/
├── src/
│   ├── Console/Commands/          # Artisan commands
│   ├── Database/Migrations/       # Database migrations
│   ├── Http/
│   │   ├── Controllers/Admin/     # Admin controllers
│   │   └── routes.php             # Route definitions
│   ├── Models/                    # Eloquent models
│   ├── Providers/                 # Service providers
│   └── Services/                  # Core services
├── resources/
│   ├── lang/en/                   # Language files
│   └── views/admin/               # Blade templates
├── publishable/
│   ├── config/                    # Configuration files
│   └── assets/                    # CSS, JS, images
├── composer.json
├── package.json
└── README.md
```

## Author

**Kevin B. Harris**
- Email: kevin.b.harris.2015@gmail.com
- GitHub: [@kevinbharris](https://github.com/kevinbharris)

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Tested With

- Bagisto 2.3.8

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/kevinbharris/ebayconnector).

## Changelog

### Version 1.0.0
- Initial release
- Product synchronization (Bagisto to eBay)
- Order synchronization (eBay to Bagisto)
- Admin panel for configuration and management
- Automatic and manual sync capabilities
- Comprehensive logging system
- Artisan commands for CLI operations
