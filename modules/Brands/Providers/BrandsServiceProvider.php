<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace Modules\Brands\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\DatabaseState;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\SettingsMenu;
use Modules\Core\Settings\SettingsMenuItem;

class BrandsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Brands';

    protected string $moduleNameLower = 'brands';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        DatabaseState::register(\Modules\Brands\Database\State\EnsureDefaultBrandIsPresent::class);

        $this->app->booted(function () {
            Innoclapps::whenReadyForServing($this->bootModule(...));
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
    }

    /**
     * Boot the module
     */
    protected function bootModule(): void
    {
        Innoclapps::booting(function () {
            SettingsMenu::register(
                SettingsMenuItem::make(__('brands::brand.brands'), '/settings/brands', 'ColorSwatch')->order(50),
                'brands'
            );
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
