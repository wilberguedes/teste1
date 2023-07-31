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

namespace Modules\WebForms\Submission;

use Illuminate\Support\Arr;
use Modules\Core\Contracts\Fields\Dateable;
use Modules\Core\Facades\ChangeLogger;
use Modules\Core\Fields\BelongsTo;
use Modules\Core\Fields\DateTime;
use Modules\Core\Fields\Field;
use Modules\Core\Fields\MorphMany;
use Modules\WebForms\Http\Requests\WebFormRequest;

class FormSubmissionLogger
{
    /**
     * Changelog identifier
     */
    const IDENTIFIER = 'web-form-submission-changelog';

    /**
     * Initialize new FormSubmissionLogger instance
     */
    public function __construct(protected array $resources, protected WebFormRequest $request)
    {
    }

    /**
     * Log the submission changelog
     *
     * @return \Modules\Core\Models\Changelog
     */
    public function log()
    {
        foreach ($this->resources as $resourceName => $model) {
            $activity = ChangeLogger::useModelLog()
                ->on($model)
                ->forceLogging()
                ->byAnonymous()
                ->identifier(static::IDENTIFIER)
                ->withProperties(
                    $this->properties($model)
                )->log();
        }

        return $activity;
    }

    /**
     * Get the changelog properties for the given model
     *
     * @param  \Modules\Core\Models\Model  $model
     * @return array
     */
    protected function properties($model)
    {
        return array_merge(
            $this->propertiesFromFieldSections($model),
            $this->propertiesFromFileSections()
        );
    }

    /**
     * Get the changelog properties from the field sections
     *
     * @param  \Modules\Core\Models\Model  $model
     * @return array
     */
    protected function propertiesFromFieldSections($model)
    {
        return $this->request->webForm()->fields()
            ->map(function (Field $field) {
                return with([
                    'value' => $this->request->getOriginalInput()[$field->requestAttribute()],
                    'attribute' => $field->attribute,
                    'label' => $field->label,
                    'resourceName' => $field->meta()['resourceName'],
                ], function ($attributes) use ($field) {
                    if (! blank($attributes['value'])) {
                        if ($field instanceof Dateable) {
                            // Dates must be formatted on front-end for proper display in user timezone
                            $attributes[$field instanceof DateTime ? 'dateTime' : 'date'] = true;
                        } else {
                            $attributes['value'] = $this->displayValueFromField($attributes['value'], $field);
                        }
                    }

                    $attributes['value'] = ! blank($attributes['value']) ?
                    $attributes['value'] :
                    null;

                    return $attributes;
                });
            })->all();
    }

    /**
     * Get the changelog properties from the file sections
     *
     * @return array
     */
    protected function propertiesFromFileSections()
    {
        return collect($this->request->webForm()->fileSections())
            ->map(function (array $section) {
                $attributes = [
                    'value' => [],
                    'label' => $section['label'],
                    'resourceName' => $section['resourceName'],
                ];

                foreach (Arr::wrap($this->request->getOriginalInput()[$section['requestAttribute']] ?? []) as $file) {
                    $attributes['value'][] = $file->getClientOriginalName().' ('.format_bytes($file->getSize()).')';
                }

                $attributes['value'] = count($attributes['value']) > 0 ? implode(', ', $attributes['value']) : null;

                return $attributes;
            })->all();
    }

    /**
     * Get the display value from the field
     *
     * @param  mixed  $value
     * @param  \Modules\Core\Fields\Field  $field
     * @return mixed
     */
    protected function displayValueFromField($value, $field)
    {
        if ($field instanceof BelongsTo) {
            $value = $field->getModel()->find($value)->{$field->labelKey};
        } elseif ($field instanceof MorphMany) {
            $value = with(collect(
                $this->request->getOriginalInput()[$field->requestAttribute()]
            ), function ($values) use ($field) {
                return $values->pluck($field->displayKey)->implode(', ');
            });
        } elseif ($field->isOptionable()) {
            $value = $this->displayValueWhenOptionableField($field, $value);
        }

        return $value;
    }

    /**
     * Get the value when optionable field
     *
     * @param  \Modules\Core\Fields\Field  $field
     * @return string
     */
    protected function displayValueWhenOptionableField($field, $value)
    {
        if ($field->isMultiOptionable()) {
            return $field->isCustomField() ? $field->customField->options
                ->whereIn('id', $value)
                ->pluck('name')
                ->implode(', ') : collect($field->resolveOptions())
                ->whereIn($field->labelKey, $value)
                ->pluck($field->labelKey)->implode(', ');
        }

        return $field->isCustomField() ?
            $field->customField->options->find($value)->name :
            collect($field->resolveOptions())
                ->firstWhere($field->labelKey, $value)
                ->{$field->labelKey};
    }
}
