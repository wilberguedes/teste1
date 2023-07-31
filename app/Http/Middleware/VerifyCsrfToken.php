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

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Modules\Core\Application;

class VerifyCsrfToken extends Middleware
{
    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        $this->except = $this->getExceptArray();

        return parent::inExceptArray($request);
    }

    /**
     * Get the except array
     *
     * Since the $except property does not allow such operations, we will
     * need to overide the inExceptArray method and perform additional coomparision
     */
    public function getExceptArray(): array
    {
        return [
            \DetachedHelper::INSTALL_ROUTE_PREFIX.'/*',
            '/forms/f/*',
            '/'.Application::API_PREFIX.'/voip/events',
            '/'.Application::API_PREFIX.'/voip/call',
            '/'.Application::API_PREFIX.'/translation/*/*',
        ];
    }
}
