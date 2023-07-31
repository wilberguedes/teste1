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

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Concerns\HasUuid;
use Modules\Core\Models\Model;
use Modules\Users\Database\Factories\UserInvitationFactory;

class UserInvitation extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The appended attributes
     *
     * @var array
     */
    protected $appends = ['link'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'roles', 'teams', 'super_admin', 'access_api'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'super_admin' => 'boolean',
        'access_api' => 'boolean',
        'roles' => 'array',
        'teams' => 'array',
    ];

    /**
     * Get the invitation link
     */
    public function link(): Attribute
    {
        return Attribute::get(fn () => route('invitation.show', ['token' => $this->token]));
    }

    /**
     * Get the model uuid column name
     */
    protected static function getUuidColumnName(): string
    {
        return 'token';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserInvitationFactory
    {
        return UserInvitationFactory::new();
    }
}
