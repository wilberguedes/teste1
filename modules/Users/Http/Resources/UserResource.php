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

namespace Modules\Users\Http\Resources;

use App\Http\Resources\ProvidesCommonData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Http\Resources\DashboardResource;
use Modules\Core\Http\Resources\RoleResource;
use Modules\Core\Resource\Http\JsonResource;

/** @mixin \Modules\Users\Models\User */
class UserResource extends JsonResource
{
    use ProvidesCommonData;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest  $request
     */
    public function toArray(Request $request): array
    {
        return $this->withCommonData([
            'name' => $this->name,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'mail_signature' => clean($this->mail_signature),
            $this->mergeWhen(! $request->isZapier(), [
                'teams' => TeamResource::collection($this->whenLoaded('teams', fn () => $this->allTeams(), [])),
                'super_admin' => $this->super_admin,
                'access_api' => $this->access_api,
                'locale' => $this->locale,
                'avatar' => $this->avatar,
                'uploaded_avatar_url' => $this->uploaded_avatar_url,
                'avatar_url' => $this->avatar_url,
                'time_format' => $this->time_format,
                'date_format' => $this->date_format,
                'first_day_of_week' => $this->first_day_of_week,
                $this->mergeWhen($this->is(Auth::user()) && $this->relationLoaded('dashboards'), [
                    'dashboards' => DashboardResource::collection($this->whenLoaded('dashboards')),
                ]),
                'notifications' => [
                    $this->mergeWhen($this->is(Auth::user()) &&
                                $this->relationLoaded('latestFifteenNotifications'), function () {
                                    return ['latest' => $this->latestFifteenNotifications];
                                }),
                    $this->mergeWhen($this->is(Auth::user()) && ! is_null($this->unread_notifications_count), [
                        'unread_count' => (int) $this->unread_notifications_count,
                    ]),
                    // Admin user edit and profile
                    $this->mergeWhen(Auth::user()->isSuperAdmin() || $this->is(Auth::user()), [
                        'settings' => Innoclapps::notificationsPreferences($this->resource),
                    ]),
                ],
                $this->mergeWhen($this->is(Auth::user()), function () {
                    return [
                        'permissions' => $this->getPermissionsViaRoles()->pluck('name'),
                    ];
                }),
                $this->mergeWhen($this->whenLoaded('roles') && Auth::user()->isSuperAdmin(), [
                    'roles' => RoleResource::collection($this->whenLoaded('roles')),
                ]),
                'gate' => [
                    'access-api' => $this->resource->can('access-api'),
                    'is-super-admin' => $this->resource->isSuperAdmin(),
                ],
            ]),
        ], $request);
    }
}
