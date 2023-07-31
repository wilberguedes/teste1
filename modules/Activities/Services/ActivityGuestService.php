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

namespace Modules\Activities\Services;

use Illuminate\Notifications\Notification;
use Modules\Activities\Models\Activity;

class ActivityGuestService
{
    /**
     * Save activity guests
     *
     * @link https://stackoverflow.com/questions/34809469/laravel-many-to-many-relationship-to-multiple-models
     *
     * @param  \Modules\Activities\Contracts\Attendeeable[]  $guests
     */
    public function save(Activity $activity, array $guests, bool $notify = true): array
    {
        $currentGuests = $activity->guests()->with('guestable')->get();

        // First, we will check the guests that we need to detach by
        // checking if the current guestable does not exists in the actual provided guests
        $detachGuests = $currentGuests->filter(function ($currentGuests) use ($guests) {
            return ! collect($guests)->first(function ($guest) use ($currentGuests) {
                return $guest->getKey() === $currentGuests->guestable->getKey() &&
                $currentGuests->guestable::class === $guest::class;
            });
        });

        // Next we will check the new guests we need to attach by checking if the
        // provided guestable does not exists in the current guests
        $attachGuests = collect($guests)->filter(function ($guest) use ($currentGuests) {
            return ! $currentGuests->first(function ($currentGuests) use ($guest) {
                return $guest->getKey() === $currentGuests->guestable->getKey() &&
                $currentGuests->guestable::class === $guest::class;
            });
        })->all();

        $activity->addGuest($attachGuests, $notify);

        $detachGuests->each->delete();

        return ['attached' => $attachGuests, 'detached' => $detachGuests->all()];
    }

    /**
     * Save activity guests without actually trying to send notification
     */
    public function saveWithoutNotifications(Activity $activity, array $guests): array
    {
        return $this->save($activity, $guests, false);
    }
}
