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

use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\UniqueResourceRule;
use Modules\Deals\Http\Resources\StageResource;
use Modules\Deals\Models\Stage;

class PipelineStage extends Resource implements Resourceful
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Deals\Models\Stage';

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return StageResource::class;
    }

    /**
     * Get the resource rules available for create
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'pipeline_id' => ['required', 'numeric'],
            'name' => array_filter([
                'required',
                'max:191',
                'string',
                // Validate after the pipeline_id is provided
                $request->filled('pipeline_id') ? UniqueResourceRule::make(
                    Stage::class,
                    'resourceId'
                )->where('pipeline_id', $request->integer('pipeline_id')) : null,
            ]),
            'win_probability' => 'required|integer|max:100|min:0',
            'display_order' => 'sometimes|integer',
        ];
    }
}
