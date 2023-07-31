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
        if (is_null($this->getContactsTableFirstNameLastNameIndexName())) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['first_name', 'last_name']);
            });
        }
    }

    public function shouldRun(): bool
    {
        return is_null($this->getContactsTableFirstNameLastNameIndexName());
    }

    protected function getContactsTableFirstNameLastNameIndexName()
    {
        foreach ($this->getContactsTableFirstNameAndLastNameIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'first_name_last_name_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getContactsTableFirstNameAndLastNameIndexes()
    {
        return $this->getColumnIndexes('contacts', 'first_name');
    }
};
