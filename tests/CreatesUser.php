<?php

namespace Tests;

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;
use Modules\Users\Models\User;

trait CreatesUser
{
    /**
     * The permissions for the role
     * By default the role does not have any permissions
     *
     * @var array
     */
    protected $withPermissionsTo = [];

    /**
     * The user create attributes to merge
     *
     * @see  signIn method
     *
     * @var array
     */
    protected $userAttributes = ['super_admin' => 1];

    /**
     * Default user attributes
     *
     * @var array
     */
    protected $defaultUserAttributes = ['super_admin' => 1];

    /**
     * Create test user
     *
     * @param  mixed  $parameters
     * @return \Modules\Users\Models\User
     */
    protected function createUser(...$parameters)
    {
        $user = User::factory(...$parameters)->create($this->userAttributes);

        if (count($this->withPermissionsTo) > 0) {
            $this->giveUserPermissions($user);
        }

        return $user;
    }

    /**
     * Sign in
     *
     * @param  \Modules\Users\Models\User|null  $user
     * @return \Modules\Users\Models\User
     */
    protected function signIn($as = null)
    {
        $user = $as ?: $this->createUser();

        if (! $as && count($this->withPermissionsTo) > 0) {
            $this->giveUserPermissions($user);
        }

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Add user attributes
     *
     *
     * @return self
     */
    protected function withUserAttrs(array $attributes)
    {
        $this->userAttributes = array_merge($this->userAttributes, $attributes);

        return $this;
    }

    /**
     * As regular user helper
     *
     * @return self
     */
    protected function asRegularUser()
    {
        $this->withUserAttrs(['super_admin' => 0]);

        return $this;
    }

    /**
     * With permissions to
     *
     * @param  array  $permissions
     * @return self
     */
    protected function withPermissionsTo($permissions = [])
    {
        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }

        $this->withPermissionsTo = $permissions;

        return $this;
    }

    /**
     * Set user attributes
     *
     *
     * @return self
     */
    protected function userAttrs(array $attributes)
    {
        $this->userAttributes = $attributes;

        return $this;
    }

    /**
     * Assign the provide user permissions to the given user
     *
     * @param  \Modules\Users\Models\User  $user
     * @return void
     */
    private function giveUserPermissions($user)
    {
        $role = $this->createRole();

        $role->givePermissionTo($this->withPermissionsTo);
        $user->assignRole($role);

        // Reset attributes in case $this->createUser() is called again
        $this->withPermissionsTo = [];
        $this->userAttributes = $this->defaultUserAttributes;
    }

    /**
     * Create new role
     *
     * @param  string|null  $name
     * @param  string  $guardName
     * @return \Modules\Core\Models\Role
     */
    protected function createRole($name = null, $guardName = 'api')
    {
        return Role::firstOrCreate([
            'guard_name' => $guardName,
            'name' => $name ?: 'admin',
        ]);
    }

    /**
     * Create new permission
     *
     * @param  string|null  $name
     * @param  string  $guardName
     * @return \Modules\Core\Models\Permission
     */
    protected function createPermission($name = null, $guardName = 'api')
    {
        return Permission::firstOrCreate([
            'guard_name' => $guardName,
            'name' => $name ?: Str::random(),
        ]);
    }
}
