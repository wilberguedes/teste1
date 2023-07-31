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

namespace Modules\Documents\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Modules\Contacts\Resource\Company\Frontend\ViewComponent as CompanyViewComponent;
use Modules\Contacts\Resource\Contact\Frontend\ViewComponent as ContactViewComponent;
use Modules\Core\DatabaseState;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Facades\MailableTemplates;
use Modules\Core\Settings\DefaultSettings;
use Modules\Core\Workflow\Workflows;
use Modules\Deals\Resource\Frontend\ViewComponent as DealViewComponent;
use Modules\Documents\Console\Commands\SendScheduledDocuments;
use Modules\Documents\Listeners\TransferDocumentsUserData;
use Modules\Documents\Support\ToScriptProvider;
use Modules\Users\Events\TransferringUserData;

class DocumentsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Documents';

    protected string $moduleNameLower = 'documents';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        DatabaseState::register(\Modules\Documents\Database\State\EnsureDocumentTypesArePresent::class);

        DefaultSettings::addRequired('default_document_type');

        $this->app['events']->listen(TransferringUserData::class, TransferDocumentsUserData::class);

        $this->commands([
            SendScheduledDocuments::class,
        ]);

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
     * Boot the module
     */
    protected function bootModule(): void
    {
        Innoclapps::booting($this->shareDataToScript(...));

        $this->scheduleTasks();
        $this->registerNotifications();
        $this->registerMailables();
        $this->registerWorkflowTriggers();
        $this->registerRelatedRecordsDetailTab();
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
     * Register the documents module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Documents\Resource\Document::class,
            \Modules\Documents\Resource\DocumentType::class,
            \Modules\Documents\Resource\DocumentTemplate::class,
        ]);
    }

    /**
     * Register the documents module available notifications.
     */
    public function registerNotifications(): void
    {
        Innoclapps::notifications([
            \Modules\Documents\Notifications\DocumentAccepted::class,
            \Modules\Documents\Notifications\DocumentViewed::class,
            \Modules\Documents\Notifications\SignerSignedDocument::class,
            \Modules\Documents\Notifications\UserAssignedToDocument::class,
        ]);
    }

    /**
     * Register the documents module available mailables.
     */
    public function registerMailables(): void
    {
        MailableTemplates::register([
            \Modules\Documents\Mail\DocumentAccepted::class,
            \Modules\Documents\Mail\DocumentViewed::class,
            \Modules\Documents\Mail\SignerSignedDocument::class,
            \Modules\Documents\Mail\UserAssignedToDocument::class,
        ]);
    }

    /**
     * Register the documents module available workflows.
     */
    public function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Documents\Workflow\Triggers\DocumentStatusChanged::class,
        ]);
    }

    /**
     * Register the documents module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('documents', 'documents-tab')->panel('documents-tab-panel')->order(25);

        ContactViewComponent::registerTab($tab);
        CompanyViewComponent::registerTab($tab);
        DealViewComponent::registerTab($tab);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
    }

    /**
     * Schedule the document related tasks.
     */
    public function scheduleTasks(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule */
        $schedule = $this->app->make(Schedule::class);

        $sendScheduledDocumentsCommandName = 'send-scheduled-documents';

        if (Innoclapps::canRunProcess()) {
            $schedule->command(SendScheduledDocuments::class)
                ->name($sendScheduledDocumentsCommandName)
                ->everyTwoMinutes()
                ->withoutOverlapping(5);
        } else {
            $schedule->call(function () {
                Artisan::call(SendScheduledDocuments::class);
            })
                ->everyTwoMinutes()
                ->name($sendScheduledDocumentsCommandName)
                ->withoutOverlapping(5);
        }
    }

    /**
     * Share data to script.
     */
    public function shareDataToScript(): void
    {
        Innoclapps::provideToScript(call_user_func(new ToScriptProvider));
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
