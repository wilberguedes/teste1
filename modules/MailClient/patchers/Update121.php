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
        if ($this->missingAliasEmailColumn()) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->after('email', function (Blueprint $table) {
                    $table->string('alias_email')->nullable();
                });
            });
        }

        if (is_null($this->getMessageAddressesAddressIndexName())) {
            Schema::table('email_account_message_addresses', function (Blueprint $table) {
                $table->index('address');
            });
        }

        if (is_null($this->getMessageAddressesNameIndexName())) {
            Schema::table('email_account_message_addresses', function (Blueprint $table) {
                $table->index('name');
            });
        }

        if (is_null($this->getMessageAddressesAddressTypeIndexName())) {
            Schema::table('email_account_message_addresses', function (Blueprint $table) {
                $table->index('address_type');
            });
        }

        if (is_null($this->getMessagesTableSubjectIndexName())) {
            Schema::table('email_account_messages', function (Blueprint $table) {
                $table->index('subject');
            });
        }

        if (is_null($this->getAccountFoldersTableSyncableIndexName())) {
            Schema::table('email_account_folders', function (Blueprint $table) {
                $table->index('syncable');
            });
        }
    }

    public function shouldRun(): bool
    {
        return is_null($this->getMessageAddressesAddressIndexName()) ||
            is_null($this->getMessageAddressesNameIndexName()) ||
            is_null($this->getMessageAddressesAddressTypeIndexName()) ||
            is_null($this->getMessagesTableSubjectIndexName()) ||
            is_null($this->getAccountFoldersTableSyncableIndexName()) ||
            $this->missingAliasEmailColumn();
    }

    protected function missingAliasEmailColumn(): bool
    {
        return ! Schema::hasColumn('email_accounts', 'alias_email');
    }

    protected function getMessageAddressesAddressIndexName()
    {
        foreach ($this->getMessageAddressesTableIndexes('address') as $key) {
            if (str_ends_with($key->Key_name, 'address_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getMessageAddressesNameIndexName()
    {
        foreach ($this->getMessageAddressesTableIndexes('name') as $key) {
            if (str_ends_with($key->Key_name, 'name_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getMessageAddressesAddressTypeIndexName()
    {
        foreach ($this->getMessageAddressesTableIndexes('address_type') as $key) {
            if (str_ends_with($key->Key_name, 'address_type_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getMessageAddressesTableIndexes($column)
    {
        return $this->getColumnIndexes('email_account_message_addresses', $column);
    }

    protected function getMessagesTableSubjectIndexName()
    {
        foreach ($this->getMessagesTableSubjectIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'subject_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getMessagesTableSubjectIndexes()
    {
        return $this->getColumnIndexes('email_account_messages', 'subject');
    }

    protected function getAccountFoldersTableSyncableIndexName()
    {
        foreach ($this->getAccountFoldersTableSyncableIndexes() as $key) {
            if (str_ends_with($key->Key_name, 'syncable_index') && $key->Index_type == 'BTREE') {
                return $key->Key_name;
            }
        }
    }

    protected function getAccountFoldersTableSyncableIndexes()
    {
        return $this->getColumnIndexes('email_account_folders', 'syncable');
    }
};
