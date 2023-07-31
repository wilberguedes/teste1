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

namespace Modules\Deals\Observers;

use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Deal;

class DealObserver
{
    /**
     * Handle the User "saving" event.
     */
    public function saving(Deal $deal): void
    {
        if ($deal->isDirty('status')) {
            if ($deal->status === DealStatus::open) {
                $deal->fill(['won_date' => null, 'lost_date' => null, 'lost_reason' => null]);
            } elseif ($deal->status === DealStatus::lost) {
                $deal->fill(['lost_date' => now(), 'won_date' => null]);
            } else {
                // won status
                $deal->fill(['won_date' => now(), 'lost_date' => null, 'lost_reason' => null]);
            }
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(Deal $deal): void
    {
        if ($deal->status === DealStatus::open) {
            $deal->startStageHistory();
        }
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(Deal $deal): void
    {
        if ($deal->isDirty('stage_id')) {
            $deal->stage_changed_date = now();
        }

        if (! $deal->isDirty('status')) {
            // Guard these attributes when the status is not changed
            foreach (['won_date', 'lost_date'] as $guarded) {
                if ($deal->isDirty($guarded)) {
                    $deal->fill([$guarded => $deal->getOriginal($guarded)]);
                }
            }

            // Allow updating the lost reason only when status is lost
            if ($deal->status !== DealStatus::lost && $deal->isDirty('lost_reason')) {
                $deal->fill(['lost_reason' => $deal->getOriginal('lost_reason')]);
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('status')) {
            if ($deal->status === DealStatus::won || $deal->status === DealStatus::lost) {
                $deal->stopLastStageTiming();
            } else {
                // changed to open
                $deal->startStageHistory();
            }
        }

        if ($deal->wasChanged('stage_id') && $deal->status === DealStatus::open) {
            $deal->recordStageHistory($deal->stage_id);
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(Deal $deal): void
    {
        if ($deal->isForceDeleting()) {
            $deal->purge();
        } else {
            $deal->logRelatedIsTrashed(['contacts', 'companies'], [
                'key' => 'core::timeline.associate_trashed',
                'attrs' => ['displayName' => $deal->display_name],
            ]);
        }
    }

    /**
     * Handle the User "restoring" event.
     */
    public function restoring(Deal $deal): void
    {
        $deal->logToAssociatedRelationsThatRelatedInstanceIsRestored(['contacts', 'companies']);
    }
}
