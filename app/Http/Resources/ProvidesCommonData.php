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

namespace App\Http\Resources;

use Illuminate\Support\Collection;
use Modules\Activities\Contracts\Attendeeable;
use Modules\Activities\Http\Resources\ActivityResource;
use Modules\Activities\Models\Activity;
use Modules\Billable\Contracts\BillableResource as BillableResourceContract;
use Modules\Billable\Http\Resources\BillableResource;
use Modules\Core\Facades\Innoclapps;

trait ProvidesCommonData
{
    /**
     * The created follow up task for the resource.
     */
    protected static ?Activity $createdActivity = null;

    /**
     * Add common data to the resource.
     *
     * @param  \Modules\Core\Resource\Http\ResourceRequest|\Illuminate\Http\Request  $request
     */
    protected function withCommonData(array $data, $request): array
    {
        $data = parent::withCommonData($data, $request);

        $data[] = $this->mergeWhen(! is_null(static::$createdActivity), [
            'createdActivity' => new ActivityResource(static::$createdActivity),
        ]);

        static::withActivity(null);

        if (! $request->isZapier()) {
            $resourceInstance = Innoclapps::resourceByModel($this->resource);

            if ($resourceInstance instanceof BillableResourceContract && $this->relationLoaded('billable')) {
                $data[] = $this->merge([
                    'billable' => new BillableResource($this->billable),
                ]);
            }

            if ($this->resource instanceof Attendeeable) {
                $data[] = $this->merge([
                    'guest_email' => $this->getGuestEmail(),
                    'guest_display_name' => $this->getGuestDisplayName(),
                ]);
            }

            if ($this->shouldMergeAssociations()) {
                $data[] = $this->merge([
                    'associations' => $this->prepareAssociationsForResponse(),
                ]);
            }
        }

        return $data;
    }

    /**
     * Set the follow up activity which was created to merge in the resource
     */
    public static function withActivity(?Activity $task): void
    {
        static::$createdActivity = $task;
    }

    /**
     * Get the resource associations
     */
    protected function prepareAssociationsForResponse(): Collection
    {
        return collect($this->resource->associatedResources())
            ->map(function ($resourceRecords) {
                return $resourceRecords->map(function ($record, $index) {
                    // Only included needed data for the front-end
                    // if needed via API, users can use the ?with= parameter to load associated resources
                    return [
                        'id' => $record->id,
                        'display_name' => $record->display_name,
                        'path' => $record->path,
                        'is_primary_associated' => $index === 0,
                    ];
                });
            });
    }

    /**
     * Check whether a resource has associations and should be merged
     *
     * Associations are merged only if they are previously eager loaded
     */
    protected function shouldMergeAssociations(): bool
    {
        if (! method_exists($this->resource, 'associatedResources')) {
            return false;
        }

        return $this->resource->associationsLoaded();
    }
}
