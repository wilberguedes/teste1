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

namespace Modules\Users\Support;

use Illuminate\Support\Facades\Auth;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Models\User;

class ToScriptProvider
{
    /**
     * Provide the data to script.
     */
    public function __invoke(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return [
            'user_id' => Auth::id(),
            'users' => UserResource::collection(User::withCommon()->get()),
            'invitation' => [
                'expires_after' => config('users.invitation.expires_after'),
            ],
        ];
    }
}
