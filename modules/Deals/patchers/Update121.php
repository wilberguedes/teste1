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
        if (is_null($this->getDealsTableNameIndexName())) {
            Schema::table('deals', function (Blueprint $table) {
                $table->index('name');
            });
        }
    }

    public function shouldRun(): bool
    {
        return is_null($this->getDealsTableNameIndexName());
    }

    protected function getDealsTableNameIndexName()
    {
        foreach ($this->getDealsTableNameIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'name_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getDealsTableNameIndexes()
    {
        return $this->getColumnIndexes('deals', 'name');
    }
};
