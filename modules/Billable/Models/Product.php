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

namespace Modules\Billable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Billable\Database\Factories\ProductFactory;
use Modules\Core\Concerns\HasCreator;
use Modules\Core\Concerns\Prunable;
use Modules\Core\Contracts\Presentable;
use Modules\Core\Models\Model;
use Modules\Core\Resource\Resourceable;

class Product extends Model implements Presentable
{
    use HasCreator,
        HasFactory,
        Resourceable,
        SoftDeletes,
        Prunable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'created_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'unit_price' => 'decimal:3',
        'direct_cost' => 'decimal:3',
        'tax_rate' => 'decimal:3',
        'created_by' => 'int',
    ];

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'name' => 'like',
        'sku',
        'is_active',
        'created_by',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                $model->billables->each->delete();
            }
        });
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the product billable products.
     */
    public function billables(): HasMany
    {
        return $this->hasMany(\Modules\Billable\Models\BillableProduct::class, 'product_id');
    }

    /**
     * Get the model display name.
     */
    public function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->name);
    }

    /**
     * Get the URL path.
     */
    public function path(): Attribute
    {
        return Attribute::get(fn () => "/products/{$this->id}");
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
