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

namespace Modules\Documents\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Documents\Database\State\EnsureDocumentTypesArePresent;

class DocumentsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        call_user_func(new EnsureDocumentTypesArePresent);
    }
}
