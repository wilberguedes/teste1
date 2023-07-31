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

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Facades\MailableTemplates;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Translator\Translator;

class SystemToolsController extends ApiController
{
    /**
     * Generate i18n file.
     */
    public function i18n(): void
    {
        // i18n tool flag

        Translator::generateJsonLanguageFile();
    }

    /**
     * Finalize the recent update.
     */
    public function finalizeUpdate(): void
    {
        // Finalize update tool flag

        Artisan::call('updater:finalize');
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): void
    {
        // Clear cache tool flag

        Artisan::call('concord:clear-cache');

        // Restart the queue (if configured)
        try {
            Artisan::call('queue:restart');
        } catch (\Exception) {
        }
    }

    /**
     * Create application storage link.
     */
    public function storageLink(): void
    {
        // Storage link tool flag

        Artisan::call('storage:link');
    }

    /**
     * Run the database migrations.
     */
    public function migrate(): void
    {
        // Migrate tool flag

        Artisan::call('migrate --force');
    }

    /**
     * Cache the framework bootstrap files.
     */
    public function optimize(): void
    {
        // Optimize tool flag

        Artisan::call('concord:optimize');

        // Restart the queue (if configured)
        try {
            Artisan::call('queue:restart');
        } catch (\Exception) {
        }
    }

    /**
     * Seed the mailable templates.
     */
    public function seedMailableTemplates(): void
    {
        // Seed mailable templates tool flag

        MailableTemplates::seedIfRequired();
    }
}
