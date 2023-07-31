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

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Concerns\HasCreator;

class Workflow extends Model
{
    use HasCreator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'description', 'trigger_type', 'action_type', 'data', 'created_by', 'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'total_executions' => 'int',
        'created_by' => 'int',
    ];

    /**
     * Scope a query to only include workflows of a given trigger type.
     */
    public function scopeByTrigger(Builder $query, string $triggerType): void
    {
        $query->where('trigger_type', $triggerType);
    }

    /**
     * Scope a query to only include active workflows.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', 1);
    }
}
