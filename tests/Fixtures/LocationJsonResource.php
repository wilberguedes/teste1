<?php

namespace Tests\Fixtures;

use Modules\Core\JsonResource;

class LocationJsonResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'location_type' => $this->location_type,
        ];
    }
}
