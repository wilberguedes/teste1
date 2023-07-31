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

namespace Modules\Calls\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Calls\Database\Factories\CallFactory;
use Modules\Comments\Concerns\HasComments;
use Modules\Core\Casts\ISO8601DateTime;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;
use Modules\Core\Timeline\Timelineable;

class Call extends Model
{
    use HasComments,
        Resourceable,
        HasFactory,
        Timelineable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'body', 'date', 'call_outcome_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date' => ISO8601DateTime::class,
        'user_id' => 'int',
        'call_outcome_id' => 'int',
    ];

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'body' => 'like',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = $model->user_id ?: auth()->id();
        });

        static::deleting(function ($model) {
            $model->purge();
        });
    }

    /**
     * A call belongs to outcome
     */
    public function outcome(): BelongsTo
    {
        return $this->belongsTo(\Modules\Calls\Models\CallOutcome::class, 'call_outcome_id');
    }

    /**
     * Get all of the contacts that are assigned this call.
     */
    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\Models\Contact::class, 'callable');
    }

    /**
     * Get all of the companies that are assigned this call.
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Contacts\Models\Company::class, 'callable');
    }

    /**
     * Get all of the deals that are assigned this call.
     */
    public function deals(): MorphToMany
    {
        return $this->morphedByMany(\Modules\Deals\Models\Deal::class, 'callable');
    }

    /**
     * Get the call owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Get the timeline component for front-end
     */
    public function getTimelineComponent(): string
    {
        return 'record-tab-timeline-call';
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->withCount(['comments'])->with([
            'companies.nextActivity',
            'contacts.nextActivity',
            'deals.nextActivity',
            'user',
            'outcome',
        ]);
    }

    /**
     * Purge the call data.
     */
    public function purge(): void
    {
        foreach (['contacts', 'companies', 'deals'] as $relation) {
            $this->{$relation}()->withTrashed()->detach();
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CallFactory
    {
        return CallFactory::new();
    }
}
