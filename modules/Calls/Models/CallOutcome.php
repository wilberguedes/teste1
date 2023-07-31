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

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Calls\Database\Factories\CallOutcomeFactory;
use Modules\Core\Models\Model;

class CallOutcome extends Model
{
    use HasFactory;

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'name' => 'like',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'swatch_color',
    ];

    /**
     * Boot the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->calls()->count() > 0) {
                abort(409, __('calls::call.outcome.delete_warning'));
            }
        });
    }

    /**
     * A call outcome can have many associated calls
     */
    public function calls(): HasMany
    {
        return $this->hasMany(\Modules\Calls\Models\Call::class);
    }

    /**
     * Name attribute accessor
     *
     * Supports translation from language file
     */
    public function name(): Attribute
    {
        return Attribute::get(function (string $value, array $attributes) {
            if (! array_key_exists('id', $attributes)) {
                return $value;
            }

            $customKey = 'custom.call_outcome.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CallOutcomeFactory
    {
        return CallOutcomeFactory::new();
    }
}
