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

namespace Modules\Calls\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Calls\Models\Call;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Users\Models\User;

class CallPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any calls.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the call.
     */
    public function view(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
    }

    /**
     * Determine if the given user can create calls.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the call.
     */
    public function update(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
    }

    /**
     * Determine whether the user can delete the call.
     */
    public function delete(User $user, Call $call): bool
    {
        return (int) $user->id === (int) $call->user_id;
    }

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        $request = app(ResourceRequest::class);

        if ($ability === 'view' && $request->viaResource()) {
            return $request->findResource($request->get('via_resource'))
                ->newModel()
                ->find($request->get('via_resource_id'))
                ->calls
                ->contains(
                    $request->route()->parameter('resourceId')
                );
        }

        return null;
    }
}
