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

namespace Modules\Billable\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Billable\Enums\TaxType;
use Modules\Billable\Listeners\TransferProductsUserData;
use Modules\Billable\Models\Billable;
use Modules\Billable\Models\BillableProduct;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\DefaultSettings;
use Modules\Users\Events\TransferringUserData;

class BillableServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Billable';

    protected string $moduleNameLower = 'billable';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        DefaultSettings::addRequired('tax_label', 'TAX');
        DefaultSettings::add('tax_rate', 0);
        DefaultSettings::addRequired('tax_type', 'no_tax');
        DefaultSettings::addRequired('discount_type', 'percent');

        $this->app['events']->listen(TransferringUserData::class, TransferProductsUserData::class);

        $this->app->booted(function () {
            $this->registerResources();
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
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

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
        Innoclapps::booting($this->shareDataToScript(...));
    }

    /**
     * Register the module available resources.
     */
    protected function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Billable\Resource\Product::class,
        ]);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        Innoclapps::provideToScript([
            'options' => [
                'tax_type' => Billable::defaultTaxType()?->name,
                'tax_label' => BillableProduct::defaultTaxLabel(),
                'tax_rate' => BillableProduct::defaultTaxRate(),
                'discount_type' => BillableProduct::defaultDiscountType(),
            ],
            'taxes' => [
                'types' => TaxType::names(),
            ],
        ]);
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
