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

namespace Modules\Users\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;

class NotificationController extends ApiController
{
    /**
     * List current user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->response(
            $request->user()->notifications()->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * Retrieve current user notification.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        return $this->response(
            $request->user()->notifications()->findOrFail($id)
        );
    }

    /**
     * Set all notifications for current user as read.
     */
    public function update(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->response('', 204);
    }

    /**
     * Delete current user notification
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        return $this->response('', 204);
    }
}
