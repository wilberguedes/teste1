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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Database\Factories\DashboardFactory;
use Modules\Core\Facades\Cards;

class Dashboard extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'cards' => 'array',
        'is_default' => 'boolean',
        'user_id' => 'int',
    ];

    /**
     * Boot the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::created(static::handleMarkedAsDefault(...));
        static::updated(static::handleMarkedAsDefault(...));
    }

    /**
     * Handle dashboard marked as default.
     */
    protected static function handleMarkedAsDefault(Dashboard $model): void
    {
        if (($model->wasChanged('is_default') || $model->wasRecentlyCreated) && $model->is_default === true) {
            static::query()->where('id', '!=', $model->id)->update(['is_default' => false]);
        }
    }

    /**
     * Scope a query dashboards for the given user.
     */
    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Get the user the dashboard belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Get the default available dashboard cards
     *
     * @param  \Modules\Users\Models\User|null  $user
     * @return \Illuminate\Support\Collection
     */
    public static function defaultCards($user = null)
    {
        return Cards::registered()->filter->authorizedToSee($user)
            ->reject(fn ($card) => $card->onlyOnIndex === true)
            ->values()
            ->map(function ($card, $index) {
                return ['key' => $card->uriKey(), 'order' => $index + 1];
            });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DashboardFactory
    {
        return DashboardFactory::new();
    }
}
