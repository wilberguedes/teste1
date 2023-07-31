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

namespace Modules\Core\Contracts\VoIP;

use Illuminate\Http\Request;

interface ReceivesEvents
{
    /**
     * Set the call events URL
     *
     * @param  string  $url The URL the client events webhook should be pointed to
     */
    public function setEventsUrl(string $url): static;

    /**
     * Handle the VoIP service events request
     *
     *
     * @return mixed
     */
    public function events(Request $request);
}
