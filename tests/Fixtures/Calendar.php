<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;
use Modules\Core\Workflow\HasWorkflowTriggers;

class Calendar extends Model
{
    use HasFactory, Resourceable, HasWorkflowTriggers;

    protected $fillable = ['name', 'user_id'];

    protected $table = 'event_calendars';

    public function events()
    {
        return $this->morphToMany(Event::class, 'eventable', 'eventables');
    }
}
