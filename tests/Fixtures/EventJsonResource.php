<?php

namespace Tests\Fixtures;

use App\Http\Resources\ProvidesCommonData;
use Modules\Core\Resource\Http\JsonResource;

class EventJsonResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->withCommonData([
        ], $request);
    }
}
