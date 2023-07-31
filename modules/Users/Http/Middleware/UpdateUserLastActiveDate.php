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

namespace Modules\Users\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Models\User;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastActiveDate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // We will only update the last_active_at each minute, as it does not
        // makes sense to update the last_active_at every request.
        if (! $request->isZapier() &&
            Auth::check() &&
            Auth::user()->last_active_at?->diffInMinutes(now()) >= 1
        ) {
            $this->updateLastActiveDate();
        }

        return $response;
    }

    /**
     * Update the current user last active date.
     */
    protected function updateLastActiveDate(): void
    {
        User::withoutTimestamps(function () {
            User::where('id', Auth::id())->update([
                'last_active_at' => now(),
            ]);
        });
    }
}
