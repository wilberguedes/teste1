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
use Modules\Core\Table\Column;

class Textarea extends Field implements Customfieldable
{
    /**
     * Field component
     */
    public ?string $component = 'textarea-field';

    /**
     * Textarea rows attribute
     */
    public function rows(string|int $rows): static
    {
        $this->withMeta(['attributes' => ['rows' => $rows]]);

        return $this;
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): ?Column
    {
        $column = parent::indexColumn();

        $column->newlineable = true;

        return $column;
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\Models\Model|null  $model
     * @return \Modules\Core\Placeholders\GenericPlaceholder
     */
    public function mailableTemplatePlaceholder($model)
    {
        $placeholder = parent::mailableTemplatePlaceholder($model);

        $placeholder->newlineable = true;

        return $placeholder;
    }

    /**
     * Create the custom field value column in database
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $table
     * @param  string  $fieldId
     * @return void
     */
    public static function createValueColumn($table, $fieldId)
    {
        $table->text($fieldId)->nullable();
    }
}
