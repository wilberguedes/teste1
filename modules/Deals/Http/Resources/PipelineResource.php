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

namespace Modules\Deals\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use Illuminate\Http\Request;
use Modules\Core\Resource\Http\JsonResource;

/** @mixin \Modules\Deals\Models\Pipeline */
class PipelineResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            $this->mergeWhen($this->relationLoaded('userOrder'), function () {
                return [
                    'user_display_order' => $this->userOrder?->display_order,
                ];
            }),
            $this->mergeWhen(! $request->isZapier(), [
                'visibility_group' => $this->visibilityGroupData(),
                'flag' => $this->flag,
                'stages' => StageResource::collection($this->whenLoaded('stages')),
            ]),
        ], $request);
    }
}
