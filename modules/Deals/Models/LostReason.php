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

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;
use Modules\Core\Models\Model;

class LostReason extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'name' => 'like',
    ];

    /**
     * A lost reason has many deals
     */
    public function deals(): HasMany
    {
        return $this->hasMany(\Modules\Deals\Models\Deal::class);
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

            $customKey = 'custom.lost_reason.'.$attributes['id'];

            if (Lang::has($customKey)) {
                return __($customKey);
            } elseif (Lang::has($value)) {
                return __($value);
            }

            return $value;
        });
    }
}
