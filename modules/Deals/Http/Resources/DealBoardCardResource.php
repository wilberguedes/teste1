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

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\ProvidesModelAuthorizations;

class DealBoardCardResource extends JsonResource
{
    use ProvidesModelAuthorizations;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return with([
            'id' => $this->id,
            'name' => $this->name, // for activity create modal
            'amount' => $this->amount,
            'display_name' => $this->display_name,
            'path' => $this->path,
            'status' => $this->status->name,
            'authorizations' => $this->getAuthorizations($this->resource),
            'expected_close_date' => $this->expected_close_date,
            'incomplete_activities_for_user_count' => (int) $this->incomplete_activities_for_user_count,
            'user_id' => $this->user_id,
            'swatch_color' => $this->swatch_color,
            'stage_id' => $this->stage_id,
        ], function ($attributes) {
            if (! is_null($this->expected_close_date)) {
                $attributes['falls_behind_expected_close_date'] = $this->fallsBehindExpectedCloseDate;
            }

            return $attributes;
        });
    }
}
