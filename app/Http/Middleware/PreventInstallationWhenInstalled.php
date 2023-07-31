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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Facades\Innoclapps;
use Symfony\Component\HttpFoundation\Response;

class PreventInstallationWhenInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()) {
            if ($request->route()->getName() === 'install.finished') {
                /**
                 * Uses signed URL Laravel feature as when the installation
                 * is finished the installed file will be created and if this action
                 * is in the PreventInstallationWhenInstalled middleware, it will show 404 error as the installed
                 * file will exists but we need to show the user that the installation is finished
                 */
                if (! $request->hasValidSignature()) {
                    abort(401);
                }
            } elseif (Innoclapps::isAppInstalled()) {
                abort(404);
            }
        }

        return $next($request);
    }
}
