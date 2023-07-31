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

class PersonalAccessTokenController extends ApiController
{
    /**
     * Get all user personal access tokens.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->response($request->user()->tokens);
    }

    /**
     * Create new user personal access token.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:191',
        ]);

        return $this->response($request->user()->createToken(
            $request->name
        ), 201);
    }

    /**
     * Revoke the given user personal access token.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $request->user()->tokens()->findOrFail($id)->delete();

        return $this->response('', 204);
    }
}
