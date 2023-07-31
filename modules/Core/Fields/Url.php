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

namespace Modules\Core\Fields;

use Modules\Core\Contracts\Fields\Customfieldable;
use Modules\Core\Contracts\Fields\UniqueableCustomfield;

class Url extends Field implements Customfieldable, UniqueableCustomfield
{
    /**
     * Field component
     */
    public ?string $component = 'url-field';

    /**
     * Create the custom field value column in database
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $fieldId
     * @return void
     */
    public static function createValueColumn($table, $fieldId)
    {
        $table->string($fieldId)->nullable();
    }
}
