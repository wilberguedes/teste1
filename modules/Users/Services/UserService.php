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

namespace Modules\Users\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Contracts\Services\CreateService;
use Modules\Core\Contracts\Services\DeleteService;
use Modules\Core\Contracts\Services\Service;
use Modules\Core\Contracts\Services\UpdateService;
use Modules\Core\Models\Model;
use Modules\Users\Models\Team;
use Modules\Users\Models\User;

class UserService implements Service, CreateService, UpdateService, DeleteService
{
    public function create(array $attributes): User
    {
        if (isset($attributes['super_admin']) && (bool) $attributes['super_admin'] === true) {
            $attributes['access_api'] = true;
        }

        $attributes['password'] = Hash::make($attributes['password']);

        $user = User::create($attributes);

        $user->assignRole($attributes['roles'] ?? []);

        collect($attributes['teams'] ?? [])->each(function ($teamId) use ($user) {
            try {
                $user->teams()->attach(Team::findOrFail($teamId));
            } catch (ModelNotFoundException) {
                // In case the team is deleted before the invitation is accepted
            }
        });

        return $user;
    }

    /**
     * Update user in storage.
     *
     * @param  \Modules\Users\Models\User&\Modules\Core\Contracts\Metable  $model
     */
    public function update(Model $model, array $attributes): User
    {
        if (isset($attributes['super_admin']) && (bool) $attributes['super_admin'] === true) {
            $attributes['access_api'] = true;
        }

        if (array_key_exists('password', $attributes)) {
            if (empty($attributes['password'])) {
                unset($attributes['password']);
            } else {
                $attributes['password'] = Hash::make($attributes['password']);
            }
        }

        $model->fill($attributes)->save();

        if (isset($attributes['roles'])) {
            $model->syncRoles($attributes['roles']);
        }

        return $model;
    }

    public function delete(Model $model, ?int $transferDataTo = null): bool
    {
        if ($model->id === Auth::id()) {
            /**
             * User cannot delete own account
             */
            abort(409, __('users::user.delete_own_account_warning'));
        } elseif ($transferDataTo === $model->id) {
            /**
             * User cannot transfer the data to the same user
             */
            abort(409, __('users::user.delete_transfer_to_same_user_warning'));
        }

        /**
         * The data must be transfered because of foreign keys
         */
        (new TransferUserDataService($transferDataTo ?? Auth::id(), $model->id))();

        $model->teams()->detach();
        $model->guests()->delete();

        /**
         * Purge user non-shared filters
         *
         * Shared filters will be transfered
         */
        $model->filters()->where('is_shared', 0)->delete();

        // Purge user non shared mail templates
        // Shared templates will be transferred
        $model->predefinedMailTemplates()->where('is_shared', 0)->delete();

        $model->zapierHooks()->delete();
        $model->dashboards()->delete();
        $model->calendar?->delete();

        $model->notifications()->delete();
        $model->comments->each->delete();
        $model->imports->each->delete();

        if ($model->avatar) {
            UserAvatarService::remove($model);
        }

        $model->load('visibilityDependents.group');

        $model->visibilityDependents->each(function ($model) {
            $model->group->teams()->detach();
            $model->group->users()->detach();
        });

        $model->personalEmailAccounts->each->delete();
        $model->oAuthAccounts->each->delete();

        return $model->delete();
    }
}
