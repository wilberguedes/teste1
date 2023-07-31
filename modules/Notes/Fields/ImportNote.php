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

namespace Modules\Notes\Fields;

use Exception;
use Modules\Core\Fields\Field;
use Modules\Core\Table\Column;
use Modules\Notes\Models\Note;

class ImportNote extends Field
{
    /**
     * Field component
     */
    public ?string $component = null;

    /**
     * Initialize new ImportNote instance class
     *
     * @param  string  $attribute field attribute
     * @param  string|null  $label field label
     */
    public function __construct($attribute = 'import_note', $label = null)
    {
        parent::__construct($attribute, $label ?: __('notes::note.note'));

        $this->strictlyForImport()
            ->excludeFromZapierResponse()
            ->saveUsing(function ($request, $requestAttribute, $value, $field) {
                return function ($model) use ($request, $value) {
                    if (empty($value)) {
                        return;
                    }

                    if (! $model->notes()->where('body', $value)->exists()) {
                        $note = new Note([
                            'body' => $value,
                            'via_resource' => $request->resource()->name(),
                            'via_resource_id' => $model->getKey(),
                        ]);

                        $note->save();

                        $note->{$request->resource()->associateableName()}()->attach($model);
                    }
                };
            });
    }

    /**
     * Get the mailable template placeholder
     *
     * @param  \Modules\Core\Models\Model  $model
     * @return null
     */
    public function mailableTemplatePlaceholder($model)
    {
        return null;
    }

    /**
     * Provide the column used for index
     *
     * @return null
     */
    public function indexColumn(): ?Column
    {
        return null;
    }

    /**
     * Resolve the actual field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function resolve($model)
    {
        return null;
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return null
     */
    public function resolveForDisplay($model)
    {
        return null;
    }

    /**
     * Resolve the field value for export
     *
     * @param  \Modules\Core\Models\Model  $model
     * @return null
     */
    public function resolveForExport($model)
    {
        return null;
    }

    /**
     * Resolve the field value for import
     *
     * @param  string|null  $value
     * @param  array  $row
     * @param  array  $original
     * @return null
     */
    public function resolveForImport($value, $row, $original)
    {
        return null;
    }

    /**
     * Resolve the field value for JSON Resource
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return null
     */
    public function resolveForJsonResource($model)
    {
        return null;
    }

    /**
     * Add custom value resolver
     *
     *
     * @return static
     */
    public function resolveUsing(callable $resolveCallback)
    {
        throw new Exception(__CLASS__.' cannot have custom resolve callback');
    }

    /**
     * Add custom display resolver
     *
     *
     * @return static
     */
    public function displayUsing(callable $displayCallback)
    {
        throw new Exception(__CLASS__.' cannot have custom display callback');
    }
}
