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

use Modules\Core\Contracts\Countable;
use Modules\Core\Table\MorphManyColumn;

abstract class MorphMany extends Field implements Countable
{
    use CountsRelationship;

    /**
     * Field JSON Resource
     */
    protected ?string $jsonResource = null;

    /**
     * The display key for the model
     */
    public string $displayKey = '';

    /**
     * Field relationship name
     */
    public string $morphManyRelationship;

    /**
     * Initialize new MorphMany instance class
     *
     * @param  string  $attribute
     * @param  string|null  $label
     */
    public function __construct($attribute, $label = null)
    {
        parent::__construct($attribute, $label);

        $this->morphManyRelationship = $attribute;
    }

    /**
     * Set the JSON resource class for the HasMany relation
     */
    public function setJsonResource(?string $resourceClass)
    {
        $this->jsonResource = $resourceClass;

        return $this;
    }

    /**
     * Provide the displayable key
     */
    public function displayKey(string $key): static
    {
        $this->displayKey = $key;

        return $this;
    }

    /**
     * Resolve the field value for import
     *
     * @param  string|null  $value
     * @param  array  $row
     * @param  array  $original
     * @return array
     */
    public function resolveForImport($value, $row, $original)
    {
        $value = parent::resolveForImport(
            $value,
            $row,
            $original
        )[$this->attribute];

        if (is_string($value)) {
            $value = array_map(fn ($label) => [$this->displayKey => trim($label)], explode(',', $value));
        }

        return [$this->attribute => $value];
    }

    /**
     * Resolve the displayable field value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string|null
     */
    public function resolveForDisplay($model)
    {
        if ($this->counts()) {
            return $model->{$this->countKey()};
        }

        $value = parent::resolveForDisplay($model);

        if ($value->isNotEmpty()) {
            return $value->pluck($this->displayKey)->implode(', ');
        }
    }

    /**
     * Resolve the field value for export
     *
     * When countable and value is zero, is shown as empty
     * In this case, we cast the value as string
     *
     * @param  \Modules\Core\Models\Model  $model
     * @return string|null
     */
    public function resolveForExport($model)
    {
        return (string) parent::resolveForExport($model);
    }

    /**
     * Resolve the value for JSON Resource
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array|null
     */
    public function resolveForJsonResource($model)
    {
        if ($this->counts()) {
            // We will check if the counted relation is null, this means that
            // the relation is not loaded, we will just return null to prevent the
            // attribute to be added in the JsonResource
            return is_null($model->{$this->countKey()}) ? null : [$this->countKey() => (int) $model->{$this->countKey()}];
        }

        if ($this->shouldResolveForJson($model)) {
            return with($this->jsonResource, function ($resource) use ($model) {
                return [
                    $this->attribute => $resource::collection($this->resolve($model)),
                ];
            });
        }
    }

    /**
     * Check whether the fields values should be resolved for JSON resource
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function shouldResolveForJson($model)
    {
        return $model->relationLoaded($this->morphManyRelationship) && $this->jsonResource;
    }

    /**
     * Provide the column used for index
     */
    public function indexColumn(): MorphManyColumn
    {
        return tap(new MorphManyColumn(
            $this->morphManyRelationship,
            $this->displayKey,
            $this->label
        ), function ($column) {
            $this->counts() ?
                $column->count()->centered()->sortable() :
                $column->displayAs(fn ($model) => $this->resolveForDisplay($model));
        });
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'morphManyRelationship' => $this->morphManyRelationship,
        ]);
    }
}
