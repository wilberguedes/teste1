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

namespace Modules\Deals\Resource;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Criteria\VisibleModelsCriteria;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\UniqueResourceRule;
use Modules\Deals\Http\Resources\PipelineResource;
use Modules\Deals\Services\PipelineService;

class Pipeline extends Resource implements Resourceful
{
    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Deals\Models\Pipeline';

    /**
     * Get the resource service for CRUD operations.
     */
    public function service(): PipelineService
    {
        return new PipelineService();
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return PipelineResource::class;
    }

    /**
     * Provide the criteria that should be used to query only records that the logged-in user is authorized to view
     */
    public function viewAuthorizedRecordsCriteria(): string
    {
        return VisibleModelsCriteria::class;
    }

    /**
     * Prepare index query.
     */
    public function indexQuery(?Builder $query = null): Builder
    {
        $query = parent::indexQuery($query);

        return $query->with(['userOrder']);
    }

    /**
     * Get the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'stages' => 'sometimes|required|array',
            'stages.*.name' => 'required|distinct|max:191|string',
            'stages.*.win_probability' => 'required|integer|max:100|min:0',
            'stages.*.display_order' => 'sometimes|integer',

            'name' => [
                'required',
                'string',
                'max:191',
                UniqueResourceRule::make(static::$model),
            ],
        ];
    }

    /**
     * Get the custom validation messages for the resource
     * Useful for resources without fields.
     */
    public function validationMessages(): array
    {
        return [
            'stages.*.name.required' => __('validation.required', [
                'attribute' => Str::lower(__('deals::deal.stage.name')),
            ]),
            'stages.*.name.distinct' => __('validation.distinct', [
                'attribute' => Str::lower(__('deals::deal.stage.name')),
            ]),
            'stages.*.win_probability.required' => __('validation.required', [
                'attribute' => Str::lower(__('deals::deal.stage.win_probability')),
            ]),
        ];
    }
}
