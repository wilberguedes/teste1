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

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Rules\ValidLocaleRule;
use Modules\Core\Rules\ValidTimezoneCheckRule;
use Modules\Users\Models\User;
use Modules\Users\Models\UserInvitation;
use Modules\Users\Services\UserService;

class UserInvitationAcceptController extends Controller
{
    /**
     * Show to invitation accept form.
     */
    public function show(string $token, Request $request): View
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        $this->authorizeInvitation($invitation);

        return view('users::invitations.show', compact('invitation'));
    }

    /**
     * Accept the invitation and create account.
     */
    public function accept(string $token, Request $request, UserService $service): void
    {
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

        $this->authorizeInvitation($invitation);

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'email|max:191|unique:'.(new User())->getTable(),
            'password' => 'required|confirmed|min:6',
            'timezone' => ['required', new ValidTimezoneCheckRule],
            'locale' => ['nullable', new ValidLocaleRule],
            'date_format' => ['required', Rule::in(config('core.date_formats'))],
            'time_format' => ['required', Rule::in(config('core.time_formats'))],
            'first_day_of_week' => 'required|in:1,6,0',
        ]);

        $user = $service->create(array_merge($data, [
            'super_admin' => $invitation->super_admin,
            'access_api' => $invitation->access_api,
            'roles' => $invitation->roles,
            'teams' => $invitation->teams,
            'email' => $invitation->email,
            'notifications' => collect(Innoclapps::notificationsPreferences())->mapWithKeys(function ($setting) {
                return [$setting['key'] => $setting['channels']->mapWithKeys(function ($channel) {
                    return [$channel => true];
                })->all()];
            })->all(),
        ]));

        Auth::loginUsingId($user->id);

        $invitation->delete();
    }

    /**
     * Authorize the given invitation.
     */
    protected function authorizeInvitation(UserInvitation $invitation): void
    {
        abort_if($invitation->created_at->diffInDays() > config('users.invitation.expires_after'), 404);
    }
}
