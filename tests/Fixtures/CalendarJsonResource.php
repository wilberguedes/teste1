<?php

namespace Tests\Fixtures;

use Modules\Core\Resource\Http\JsonResource;

class CalendarJsonResource extends JsonResource
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
            'title' => $this->title,
        ];
    }
}
