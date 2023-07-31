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

namespace Modules\Calls\Providers;

use App\Http\View\FrontendComposers\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Modules\Calls\Http\Resources\CallOutcomeResource;
use Modules\Calls\Listeners\TransferCallsUserData;
use Modules\Calls\Models\CallOutcome;
use Modules\Contacts\Resource\Company\Frontend\ViewComponent as CompanyViewComponent;
use Modules\Contacts\Resource\Contact\Frontend\ViewComponent as ContactViewComponent;
use Modules\Core\DatabaseState;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Facades\Permissions;
use Modules\Core\Models\Workflow;
use Modules\Core\Workflow\Workflows;
use Modules\Deals\Resource\Frontend\ViewComponent as DealViewComponent;
use Modules\Users\Events\TransferringUserData;

class CallsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Calls';

    protected string $moduleNameLower = 'calls';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        DatabaseState::register(\Modules\Calls\Database\State\EnsureCallOutcomesArePresent::class);

        $this->app['events']->listen(TransferringUserData::class, TransferCallsUserData::class);

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
        Innoclapps::booting($this->shareDataToScript(...));

        $this->configureVoIP();
        $this->registerWorkflowTriggers();
        $this->registerRelatedRecordsDetailTab();
    }

    /**
     * Set the application VoIP Client
     */
    protected function configureVoIP(): void
    {
        $options = $this->app['config']->get('core.services.twilio');

        $totalFilled = count(array_filter($options));

        if ($totalFilled === count($options)) {
            $this->app['config']->set('core.voip.client', 'twilio');

            Permissions::register(function ($manager) {
                $manager->group(['name' => 'voip', 'as' => __('calls::call.voip_permissions')], function ($manager) {
                    $manager->view('view', [
                        'as' => __('calls::call.capabilities.use_voip'),
                        'permissions' => ['use voip' => __('calls::call.capabilities.use_voip')],
                    ]);
                });
            });
        }
    }

    /**
     * Register the module available resources.
     */
    public function registerResources(): void
    {
        Innoclapps::resources([
            \Modules\Calls\Resource\Call::class,
            \Modules\Calls\Resource\CallOutcome::class,
        ]);
    }

    /**
     * Register the module workflow triggers.
     */
    protected function registerWorkflowTriggers(): void
    {
        Workflows::triggers([
            \Modules\Calls\Workflow\Triggers\MissedIncomingCall::class,
        ]);
    }

    /**
     * Share data to script.
     */
    protected function shareDataToScript(): void
    {
        if (! Auth::check()) {
            return;
        }

        Innoclapps::provideToScript(['calls' => [
            'outcomes' => CallOutcomeResource::collection(CallOutcome::orderBy('name')->get()),
        ]]);
    }

    /**
     * Register the module related tabs.
     */
    public function registerRelatedRecordsDetailTab(): void
    {
        $tab = Tab::make('calls', 'calls-tab')->panel('calls-tab-panel')->order(30);

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
