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

namespace Modules\WebForms\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Facades\MailableTemplates;
use Modules\Core\Settings\SettingsMenu;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Users\Events\TransferringUserData;
use Modules\WebForms\Listeners\TransferWebFormUserData;

class WebFormsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'WebForms';

    protected string $moduleNameLower = 'webforms';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        MailableTemplates::register([
            \Modules\WebForms\Mail\WebFormSubmitted::class,
        ]);

        $this->app['events']->listen(TransferringUserData::class, TransferWebFormUserData::class);

        SettingsMenu::register(
            SettingsMenuItem::make(__('webforms::form.forms'), '/settings/forms', 'MenuAlt3')->order(30),
            'web-forms'
        );
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
