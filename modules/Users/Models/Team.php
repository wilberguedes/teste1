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

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Model;
use Modules\Core\Models\ModelVisibilityGroupDependent;
use Modules\Users\Database\Factories\TeamFactory;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'int',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->purge();
        });
    }

    /**
     * Get the team manager
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the users that belong to the team
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->as('membership');
    }

    /**
     * Get all of the team's users including its manager
     */
    public function allUsers(): Collection
    {
        return $this->users->merge([$this->manager]);
    }

    /**
     * Get all of the visibility dependent models
     */
    public function visibilityDependents(): MorphMany
    {
        return $this->morphMany(ModelVisibilityGroupDependent::class, 'dependable');
    }

    /**
     * Scope a query to include only the teams the user belongs to.
     */
    public function scopeUserTeams(Builder $query, ?User $user = null): void
    {
        /** @var \Modules\Users\Models\User */
        $user = $user ?: Auth::user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $query->whereHas('users', function ($query) use ($user) {
            return $query->where('user_id', $user->getKey());
        })->orWhere('teams.user_id', $user->getKey());
    }

    /**
     * Purge the team data.
     */
    public function purge(): void
    {
        $this->users()->detach();

        $this->load('visibilityDependents.group');

        $this->visibilityDependents->each(function ($model) {
            $model->group->teams()->detach();
            $model->group->users()->detach();
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
