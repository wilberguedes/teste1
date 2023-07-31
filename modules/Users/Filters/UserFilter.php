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

namespace Modules\Users\Filters;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Filters\Select;
use Modules\Users\Models\User;

class UserFilter extends Select
{
    /**
     * Initialize User class
     *
     * @param  string|null  $label
     * @param  string|null  $field
     */
    public function __construct($label = null, $field = null)
    {
        parent::__construct($field ?? 'user_id', $label ?? __('users::user.user'));

        $this->valueKey('id')->labelKey('name');
    }

    /**
     * Provides the User instance options
     *
     * @return \Illuminate\Support\Collection
     */
    public function resolveOptions()
    {
        return User::select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $isLoggedInUser = $user->is(Auth::user());

                return [
                    'id' => ! $isLoggedInUser ? $user->id : 'me',
                    'name' => ! $isLoggedInUser ? $user->name : __('core::filters.me'),
                ];
            });
    }
}
