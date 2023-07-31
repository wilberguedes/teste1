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

return new class extends UpdatePatcher
{
    public function run(): void
    {
        if (is_null($this->getActivitiesTableTitleIndexName())) {
            Schema::table('activities', function (Blueprint $table) {
                $table->index('title');
            });
        }

        if (is_null($this->getActivitiesTableDueDateDueTimeIndexName())) {
            Schema::table('activities', function (Blueprint $table) {
                $table->index(['due_date', 'due_time']);
            });
        }
    }

    public function shouldRun(): bool
    {
        return is_null($this->getActivitiesTableTitleIndexName()) ||
            is_null($this->getActivitiesTableDueDateDueTimeIndexName());
    }

    protected function getActivitiesTableTitleIndexName()
    {
        foreach ($this->getActivitiesTableTitleIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'title_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getActivitiesTableTitleIndexes()
    {
        return $this->getColumnIndexes('activities', 'title');
    }

    protected function getActivitiesTableDueDateDueTimeIndexName()
    {
        foreach ($this->getActivitiesTableDueDateDueTimeIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'due_date_due_time_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getActivitiesTableDueDateDueTimeIndexes()
    {
        return $this->getColumnIndexes('activities', 'due_date');
    }
};
