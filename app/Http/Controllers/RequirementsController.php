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

namespace App\Http\Controllers;

use App\Installer\EnvironmentManager;
use App\Installer\PermissionsChecker;
use App\Installer\RequirementsChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Modules\Core\Environment;

class RequirementsController extends Controller
{
    /**
     * Shows the requirements page.
     */
    public function show(RequirementsChecker $requirements, PermissionsChecker $permissions): View
    {
        $php = $requirements->checkPHPversion();
        $requirements = $requirements->check();
        $permissions = $permissions->check();
        $memoryLimitMB = EnvironmentManager::getMemoryLimitInMegabytes();
        $memoryLimitRaw = ini_get('memory_limit');

        ViewFacade::share(['withSteps' => false]);

        return view('requirements', [
            'php' => $php,
            'requirements' => $requirements,
            'permissions' => $permissions,
            'memoryLimitMB' => $memoryLimitMB,
            'memoryLimitRaw' => $memoryLimitRaw,
        ]);
    }

    /**
     * Confirm the requirements
     */
    public function confirm(): RedirectResponse
    {
        Environment::capture();

        return redirect()->back();
    }
}
