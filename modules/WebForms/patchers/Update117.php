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

use App\Support\ToModuleMigrator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Updater\UpdatePatcher;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        ToModuleMigrator::make('webforms')
            ->migrateMailableTemplates($this->getMailableTemplatesMap())
            ->migrateDbLanguageKeys('form')
            ->migrateLanguageFiles(['form.php'])
            ->deleteConflictedFiles([app_path('Models/WebForm.php')]);

        DB::table('changelog')
            ->where('identifier', 'web-form-submission')
            ->update(['identifier' => 'web-form-submission-changelog']);
    }

    public function shouldRun(): bool
    {
        return file_exists(app_path('Models/WebForm.php'));
    }

    protected function getMailableTemplatesMap(): array
    {
        return [
            'App\\Mail\\WebFormSubmitted' => 'Modules\WebForms\Mail\WebFormSubmitted',
        ];
    }
};
