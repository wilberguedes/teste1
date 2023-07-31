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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Updater\UpdatePatcher;
use Modules\Documents\Models\Document;

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if ($this->missingLocaleColumn()) {
            Schema::table('documents', function (Blueprint $table) {
                $table->after('view_type', function (Blueprint $table) {
                    $table->string('locale')->nullable();
                });
            });

            Document::with('user')
                ->lazyById(200)
                ->each(function ($document) {
                    $document->locale = $document->user->preferredLocale();
                    $document->saveQuietly();
                });

            Schema::table('documents', function (Blueprint $table) {
                $table->string('locale')->nullable(false)->change();
            });
        }
    }

    public function shouldRun(): bool
    {
        return $this->missingLocaleColumn();
    }

    protected function missingLocaleColumn(): bool
    {
        return ! Schema::hasColumn('documents', 'locale');
    }
};
