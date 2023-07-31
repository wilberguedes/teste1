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

namespace Modules\Users\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Modules\Activities\Contracts\Attendeeable;
use Modules\Core\Card\DashboardService;
use Modules\Core\Concerns\HasAvatar;
use Modules\Core\Concerns\HasMeta;
use Modules\Core\Contracts\HasNotificationsSettings;
use Modules\Core\Contracts\Localizeable;
use Modules\Core\Contracts\Metable;
use Modules\Core\Media\HasAttributesWithPendingMedia;
use Modules\Core\Models\Dashboard;
use Modules\Core\Models\Filter;
use Modules\Core\Models\Model;
use Modules\Core\Models\ModelVisibilityGroupDependent;
use Modules\Core\Models\ZapierHook;
use Modules\Users\Concerns\HasTeams;
use Modules\Users\Database\Factories\UserFactory;
use Modules\Users\Notifications\ResetPassword as ResetPasswordNotification;
use Spatie\Permission\Traits\HasRoles;

class User extends Model implements HasLocalePreference, Localizeable, Metable, Attendeeable, AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, HasNotificationsSettings
{
    use Notifiable,
        HasApiTokens,
        HasRoles,
        HasMeta,
        HasAttributesWithPendingMedia,
        HasFactory,
        HasAvatar,
        Authenticatable,
        Authorizable,
        CanResetPassword,
        HasTeams;

    /**
     * Permissions guard name
     *
     * @var string
     */
    public $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'timezone',
        'date_format', 'time_format', 'first_day_of_week',
        'locale', 'mail_signature', 'super_admin', 'access_api',
        'notifications_settings',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_active_at' => 'datetime',
        'super_admin' => 'boolean',
        'access_api' => 'boolean',
        'first_day_of_week' => 'int',
        'notifications_settings' => 'array',
    ];

    /**
     * The columns for the model that are searchable.
     */
    protected static array $searchableColumns = [
        'email',
        'name' => 'like',
    ];

    public function getNotificationPreference(string $key): array
    {
        return $this->notifications_settings[$key] ?? [];
    }

    /**
     * Boot User model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($user) {
            static::createDefaults($user);
        });
    }

    /**
     * Check whether data can be synced to the user calendar
     */
    public function canSyncToCalendar(): bool
    {
        return ! is_null($this->calendar) && $this->calendar->synchronization->enabled();
    }

    /**
     * Get all the user connected oAuth accounts
     */
    public function oAuthAccounts(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\OAuthAccount::class);
    }

    /**
     * Get the user connected oAuth calendar
     */
    public function calendar(): HasOne
    {
        return $this->hasOne(\Modules\Activities\Models\Calendar::class)
            ->whereHas('synchronization', function ($query) {
                return $query->notDisabled();
            });
    }

    /**
     * Get all of the data imports instances for the user.
     */
    public function imports(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\Import::class);
    }

    /**
     * User can have many companies
     */
    public function companies(): HasMany
    {
        return $this->hasMany(\Modules\Contacts\Models\Company::class);
    }

    /**
     * User has many comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(\Modules\Comments\Models\Comment::class, 'created_by');
    }

    /**
     * User can have many contacts
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(\Modules\Contacts\Models\Contact::class);
    }

    /**
     * User can have many deals
     */
    public function deals(): HasMany
    {
        return $this->hasMany(\Modules\Deals\Models\Deal::class);
    }

    /**
     * User has created many deals
     */
    public function createdDeals(): HasMany
    {
        return $this->hasMany(\Modules\Deals\Models\Deal::class, 'created_by');
    }

    /**
     * User can be assigned to many activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\Modules\Activities\Models\Activity::class);
    }

    /**
     * User has many Zapier hooks
     */
    public function zapierHooks(): HasMany
    {
        return $this->hasMany(ZapierHook::class);
    }

    /**
     * Get the shared email accounts the user connected
     */
    public function sharedEmailAccounts(): HasMany
    {
        return $this->hasMany(\Modules\MailClient\Models\EmailAccount::class, 'created_by')->whereNull('user_id');
    }

    /**
     * A user has many personal email accounts configured
     */
    public function personalEmailAccounts(): HasMany
    {
        return $this->hasMany(\Modules\MailClient\Models\EmailAccount::class);
    }

    /**
     * A user has many predefined mail templates configured
     */
    public function predefinedMailTemplates(): HasMany
    {
        return $this->hasMany(\Modules\MailClient\Models\PredefinedMailTemplate::class);
    }

    /**
     * A user has many dashboards
     */
    public function dashboards(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\Dashboard::class);
    }

    /**
     * Get the default dashboard for the current user.
     */
    public function defaultDashboard(): Dashboard
    {
        return $this->dashboards()->where('is_default', true)->first();
    }

    /**
     * Check whether the user has only one dashboard.
     */
    public function hasOnlyOneDashboard(): bool
    {
        return $this->dashboards->count() === 1;
    }

    /**
     * User can have many filters
     */
    public function filters(): HasMany
    {
        return $this->hasMany(\Modules\Core\Models\Filter::class);
    }

    /**
     * Check whether the user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->super_admin === true;
    }

    /**
     * Check whether the user has api access
     */
    public function hasApiAccess(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->access_api === true;
    }

    /**
     * Get the user's preferred locale.
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the user time format
     */
    public function getLocalTimeFormat(): string
    {
        return $this->time_format;
    }

    /**
     * Get the user date format
     */
    public function getLocalDateFormat(): string
    {
        return $this->date_format;
    }

    /**
     * Get the user timezone
     */
    public function getUserTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Latest 15 user notifications help relation
     */
    public function latestFifteenNotifications(): MorphMany
    {
        return $this->notifications()->take(15);
    }

    /**
     * Get all of the user guests models
     */
    public function guests(): MorphMany
    {
        return $this->morphMany(\Modules\Activities\Models\Guest::class, 'guestable');
    }

    /**
     * Get all of the activities the user created
     */
    public function createdActivities(): HasMany
    {
        return $this->hasMany(\Modules\Activities\Models\Activity::class, 'created_by');
    }

    /**
     * Get all of the visibility dependent models
     */
    public function visibilityDependents(): MorphMany
    {
        return $this->morphMany(ModelVisibilityGroupDependent::class, 'dependable');
    }

    /**
     * Get the person email address when guest
     */
    public function getGuestEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the person displayable name when guest
     */
    public function getGuestDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get the notification that should be sent to the person when is added as guest
     *
     * @return string
     */
    public function getAttendeeNotificationClass()
    {
        return \Modules\Activities\Notifications\UserAttendsToActivity::class;
    }

    /**
     * Indicates whether the notification should be send to the guest
     *
     * @param  \Modules\Activities\Contracts\Attendeeable&\Illuminate\Database\Eloquent\Model  $model
     */
    public function shouldSendAttendingNotification(Attendeeable $model): bool
    {
        // Otherwise is handled via user profile notification settings
        // e.q. if user turned off, it won't be sent
        return ! $model->is(Auth::user());
    }

    /**
     * Get the attributes that may contain pending media
     */
    public function attributesWithPendingMedia(): string
    {
        return 'mail_signature';
    }

    /**
     * Create the user default data
     */
    protected static function createDefaults(User $user): void
    {
        (new DashboardService())->createDefault($user);

        if ($openDealsFilter = Filter::findByFlag('open-deals')) {
            $openDealsFilter->markAsDefault(['deals', 'deals-board'], $user->id);
        }

        if ($openActivitiesFilter = Filter::findByFlag('open-activities')) {
            $openActivitiesFilter->markAsDefault('activities', $user->id);
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Eager load the relations that are required for the front end response.
     */
    public function scopeWithCommon(Builder $query): void
    {
        $query->withCount('unreadNotifications')->with([
            'latestFifteenNotifications',
            'roles.permissions',
            'dashboards',
            'managedTeams',
            'teams',
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
