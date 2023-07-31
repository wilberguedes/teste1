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

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Facades\VoIP;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Core\VoIP\Events\IncomingCallMissed;

class VoIPController extends ApiController
{
    /**
     * Call events
     *
     * @return mixed
     */
    public function events(Request $request)
    {
        VoIP::validateRequest($request);

        $call = VoIP::getCall($request);

        if ($call->isMissedIncoming()) {
            event(new IncomingCallMissed($call));
        }

        return VoIP::events($request);
    }

    /**
     * Initiate new call
     *
     * @return mixed
     */
    public function newCall(Request $request)
    {
        VoIP::validateRequest($request);

        if (VoIP::shouldReceivesEvents()) {
            VoIP::setEventsUrl(VoIP::eventsUrl());
        }

        if ($request->boolean('viaApp')) {
            return VoIP::newOutgoingCall(
                $request->input('To'),
                $request
            );
        }

        return VoIP::newIncomingCall($request);
    }

    /**
     * Create a new client token.
     */
    public function newToken(Request $request): JsonResponse
    {
        return $this->response(['token' => VoIP::newToken($request)]);
    }
}
