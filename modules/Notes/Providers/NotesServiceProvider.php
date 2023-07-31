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

namespace Modules\Notes\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Support\ServiceProvider;
use Modules\Contacts\Resource\Company\Frontend\ViewComponent as CompanyViewComponent;
use Modules\Contacts\Resource\Contact\Frontend\ViewComponent as ContactViewComponent;
use Modules\Core\Facades\Innoclapps;
use Modules\Deals\Resource\Frontend\ViewComponent as DealViewComponent;
use Modules\Notes\Listeners\TransferNotesUserData;
use Modules\Users\Events\TransferringUserData;

class NotesServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Notes';

    protected string $moduleNameLower = 'notes';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->app['events']->listen(TransferringUserData::class, TransferNotesUserData::class);

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
     * Boot the module.
     */
    protected function bootModule(): void
    {
        $this->registerRelatedRecordsDetailTab();
    }

    /**
     * Register the module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Notes\Resource\Note::class,
        ]);
    }

    /**
     * Register the module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('notes', 'notes-tab')->panel('notes-tab-panel')->order(35);

        ContactViewComponent::registerTab($tab);
        CompanyViewComponent::registerTab($tab);
        DealViewComponent::registerTab($tab);
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
