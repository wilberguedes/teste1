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

namespace App\Providers;

use App\Console\Commands\ClearCacheCommand;
use App\Console\Commands\ClearStaleViteAssets;
use App\Console\Commands\OptimizeCommand;
use App\Support\CommonPermissionsProvider;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Console\Commands\ClearUpdaterTmpPathCommand;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\DefaultSettings;
use Modules\Translator\Console\Commands\GenerateJsonLanguageFileCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useScriptTagAttributes(fn (string $src, string $url, array|null $chunk, array|null $manifest) => [
            'onload' => $src === 'resources/js/app.js' ? 'bootApplication()' : false,
        ]);

        if ($this->app->runningInConsole() && ! empty($this->app['config']->get('app.cli_memory_limit'))) {
            \DetachedHelper::raiseMemoryLimit($this->app['config']->get('app.cli_memory_limit'));
        }

        if ($this->app['config']->get('app.force_ssl')) {
            URL::forceScheme('https');
        }

        Model::preventLazyLoading(! $this->app->isProduction());

        Schema::defaultStringLength(191);

        JsonResource::withoutWrapping();

        $this->app['config']->set('core.resources.permissions.common', CommonPermissionsProvider::class);

        $this->configureUpdater();

        Innoclapps::whenInstalled($this->configureBroadcasting(...));

        DefaultSettings::add('disable_password_forgot', false);

        View::composer('components/layouts/auth', \Modules\Core\Http\View\Composers\AppComposer::class);
    }

    /**
     * Set the broadcasting driver
     */
    protected function configureBroadcasting(): void
    {
        if (Innoclapps::hasBroadcastingConfigured()) {
            $this->app['config']->set('broadcasting.default', 'pusher');
        }
    }

    /**
     * Configure the core updater.
     */
    protected function configureUpdater(): void
    {
        $this->app['config']->set('updater.optimize', OptimizeCommand::class);

        $this->app['config']->set('updater.commands.post_update', [
            ClearCacheCommand::class,
            ClearUpdaterTmpPathCommand::class,
            [
                'class' => MigrateCommand::class,
                'params' => ['--force' => true],
            ],
        ]);

        $this->app['config']->set('updater.commands.finalize', [
            ClearCacheCommand::class,
            GenerateJsonLanguageFileCommand::class,
            ClearStaleViteAssets::class,
        ]);
    }
}
