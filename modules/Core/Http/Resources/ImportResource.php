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

namespace Modules\Core\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use Illuminate\Http\Request;
use Modules\Core\JsonResource;
use Modules\Users\Http\Resources\UserResource;

/** @mixin \Modules\Core\Models\Import */
class ImportResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'file_name' => $this->file_name,
            'skip_file_filename' => $this->skip_file_filename,
            'mappings' => $this->data['mappings'],
            'resource_name' => $this->resource_name,
            'status' => $this->status,
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'duplicates' => $this->duplicates,
            'fields' => $this->fields(),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'completed_at' => $this->completed_at,
        ], $request);
    }
}
