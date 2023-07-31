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

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\Contracts\Manager as ManagerContract;
use Modules\Core\Settings\Contracts\Store as StoreContract;
use Modules\Core\Settings\DefaultSettings;
use Modules\Core\Settings\SettingsManager;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Tmp for v1.1.7
        if (is_file(config_path('innoclapps.php'))) {
            File::delete(config_path('innoclapps.php'));
            File::delete(app_path('Console/Commands/FinalizeUpdateCommand.php'));
            File::delete(app_path('Console/Commands/GenerateJsonLanguageFileCommand.php'));
            File::delete(app_path('Console/Commands/SendScheduledDocuments.php'));
            File::delete(app_path('Console/Commands/ActivitiesNotificationsCommand.php'));
            File::delete(app_path('Console/Commands/UpdateCommand.php'));
            File::delete(config_path('updater.php'));
            File::delete(config_path('settings.php'));
            File::delete(config_path('fields.php'));
            if (is_file(config_path('purifier.php'))) {
                File::delete(config_path('purifier.php'));
            }
            File::delete(config_path('html_purifier.php'));

            exit(header('Location: /dashboard'));
        }

        $this->app->singleton(ManagerContract::class, SettingsManager::class);

        $this->app->singleton(StoreContract::class, function ($app) {
            return $app[ManagerContract::class]->driver();
        });

        $this->app->extend(ManagerContract::class, function (ManagerContract $manager, $app) {
            foreach ($app['config']->get('settings.drivers', []) as $driver => $params) {
                $manager->registerStore($driver, $params);
            }

            return $manager;
        });
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $this->registerDefaults();

        $this->app->booted(function () {
            Innoclapps::whenInstalled($this->setUserDefinedExtensionsInConfig(...));
        });

        Innoclapps::whenInstalled(
            $this->app[ManagerContract::class]->driver()->configureOverrides(...)
        );
    }

    /**
     * Register the default settings.
     */
    protected function registerDefaults(): void
    {
        DefaultSettings::addRequired('date_format', 'F j, Y');
        DefaultSettings::addRequired('time_format', 'H:i');
        DefaultSettings::add('block_bad_visitors', false);
        DefaultSettings::addRequired('currency', 'USD');
        DefaultSettings::addRequired('first_day_of_week', 0); // sunday
        DefaultSettings::addRequired('allowed_extensions', 'jpg, jpeg, png, gif, svg, pdf, aac, ogg, oga, mp3, wav, mp4, m4v,mov, ogv, webm, zip, rar, doc, docx, txt, text, xml, json, xls, xlsx, odt, csv, ppt, pptx, ppsx, ics, eml');
    }

    /**
     * Set application allowed media extensions
     */
    protected function setUserDefinedExtensionsInConfig(): void
    {
        // Replace dots with empty in case the user add dot in the extension name
        $this->app['config']->set('mediable.allowed_extensions', array_map(
            fn ($extension) => trim(Str::replaceFirst('.', '', $extension)),
            // use the get method because of 1.0.6 changes in settings function, fails on update if not used
            explode(',', settings()->get('allowed_extensions'))
        ));
    }
}
