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
        if (! $this->getPhonesTableNumberColumnIndexName()) {
            Schema::table('phones', function (Blueprint $table) {
                $table->index('number');
            });
        }
    }

    public function shouldRun(): bool
    {
        return ! $this->getPhonesTableNumberColumnIndexName();
    }

    protected function getPhonesTableNumberColumnIndexName()
    {
        foreach ($this->getColumnIndexes('phones', 'number') as $key) {
            if (str_ends_with($key->Key_name, 'number_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }
};
