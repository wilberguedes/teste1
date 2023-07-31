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

namespace Modules\Users\Resource;

use Illuminate\Validation\Rule;
use Modules\Core\Contracts\Resources\Resourceful;
use Modules\Core\Contracts\Resources\Tableable;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Resource\Resource;
use Modules\Core\Rules\UniqueResourceRule;
use Modules\Core\Rules\ValidLocaleRule;
use Modules\Core\Rules\ValidTimezoneCheckRule;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Table\Table;
use Modules\Users\Http\Resources\UserResource;
use Modules\Users\Services\UserService;

class User extends Resource implements Resourceful, Tableable
{
    /**
     * The column the records should be default ordered by when retrieving
     */
    public static string $orderBy = 'name';

    /**
     * The model the resource is related to
     */
    public static string $model = 'Modules\Users\Models\User';

    /**
     * Get the resource service for CRUD operations.
     */
    public function service(): UserService
    {
        return new UserService();
    }

    /**
     * Provide the resource table class
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function table($query, ResourceRequest $request): Table
    {
        return (new UserTable($query, $request))->orderBy('name', 'asc');
    }

    /**
     * Get the json resource that should be used for json response
     */
    public function jsonResource(): string
    {
        return UserResource::class;
    }

    /**
     * Get the resource rules available for create and update
     */
    public function rules(ResourceRequest $request): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'password' => [
                $request->route('resourceId') ? 'nullable' : 'required', 'confirmed', 'min:6',
            ],
            'email' => [
                'required',
                'email',
                'max:191',
                UniqueResourceRule::make(static::$model),
            ],
            'locale' => ['nullable', new ValidLocaleRule],
            'timezone' => ['required', 'string', new ValidTimezoneCheckRule],
            'time_format' => ['required', 'string', Rule::in(config('core.time_formats'))],
            'date_format' => ['required', 'string', Rule::in(config('core.date_formats'))],
            'first_day_of_week' => ['required', 'in:1,6,0', 'numeric'],
        ];
    }

    /**
     * Provides the resource available actions
     */
    public function actions(ResourceRequest $request): array
    {
        return [
            (new \Modules\Users\Actions\UserDelete)->canSeeWhen('is-super-admin'),
        ];
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        return [
            SettingsMenuItem::make(__('users::user.users'), '/settings/users', 'Users')->order(41),
        ];
    }
}
