<?php

namespace KevinBHarris\EbayConnector\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use KevinBHarris\EbayConnector\Console\Commands\SyncProductsCommand;
use KevinBHarris\EbayConnector\Console\Commands\SyncOrdersCommand;

class EbayConnectorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/publishable/config/ebayconnector.php', 
            'ebayconnector'
        );
        
        // Register system configuration for Bagisto admin panel
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/publishable/config/system.php',
            'core'
        );
        
        // Merge menu configuration
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/publishable/config/menu.php',
            'menu.admin'
        );
        
        // Merge ACL configuration
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/publishable/config/acl.php',
            'acl'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(dirname(__DIR__) . '/Database/Migrations');
        
        // Load routes
        Route::group([
            'prefix' => 'admin/ebayconnector',
            'middleware' => ['web', 'admin']
        ], function () {
            require dirname(__DIR__) . '/Http/routes.php';
        });
        
        // Load public routes (without admin middleware)
        Route::group([
            'middleware' => ['web']
        ], function () {
            Route::get('ebay/oauth/callback', [\KevinBHarris\EbayConnector\Http\Controllers\AuthController::class, 'callback'])->name('ebay.oauth.callback');
            Route::get('ebay/auth/accepted', [\KevinBHarris\EbayConnector\Http\Controllers\AuthController::class, 'accepted'])->name('ebay.auth.accepted');
            Route::get('ebay/auth/rejected', [\KevinBHarris\EbayConnector\Http\Controllers\AuthController::class, 'rejected'])->name('ebay.auth.rejected');
        });
        
        // Load views
        $this->loadViewsFrom(dirname(__DIR__, 2) . '/resources/views', 'ebayconnector');
        
        // Load translations
        $this->loadTranslationsFrom(dirname(__DIR__, 2) . '/resources/lang', 'ebayconnector');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncProductsCommand::class,
                SyncOrdersCommand::class,
            ]);

            // Publish configuration files
            $this->publishes([
                dirname(__DIR__, 2) . '/publishable/config/ebayconnector.php' => config_path('ebayconnector.php'),
            ], 'ebayconnector-config');
            
            $this->publishes([
                dirname(__DIR__, 2) . '/publishable/config/system.php' => config_path('ebayconnector_system.php'),
            ], 'ebayconnector-config');
            
            $this->publishes([
                dirname(__DIR__, 2) . '/publishable/config/menu.php' => config_path('ebayconnector_menu.php'),
            ], 'ebayconnector-config');
            
            $this->publishes([
                dirname(__DIR__, 2) . '/publishable/config/acl.php' => config_path('ebayconnector_acl.php'),
            ], 'ebayconnector-config');
            
            // Publish views
            $this->publishes([
                dirname(__DIR__, 2) . '/resources/views' => resource_path('views/vendor/ebayconnector'),
            ], 'ebayconnector-views');
            
            // Publish assets (CSS and fonts)
            $this->publishes([
                dirname(__DIR__, 2) . '/publishable/assets' => public_path('vendor/ebayconnector'),
            ], 'ebayconnector-assets');
        }

        // Inject the CSS into the Bagisto admin layout
		view()->composer('*', function ($view) {
			$view->getFactory()->startPush('styles');
			echo '<link rel="stylesheet" href="' . asset('vendor/ebayconnector/css/app.css') . '">';
			$view->getFactory()->stopPush();
		});
        
        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(
            \KevinBHarris\EbayConnector\Events\ProductSyncedToEbay::class,
            \KevinBHarris\EbayConnector\Listeners\SyncProductOnCreate::class
        );
        
        Event::listen(
            \KevinBHarris\EbayConnector\Events\OrderSyncedFromEbay::class,
            \KevinBHarris\EbayConnector\Listeners\SyncProductOnUpdate::class
        );
    }
}
