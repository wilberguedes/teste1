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

namespace Modules\Deals\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Billable\Services\BillableService;
use Modules\Core\Contracts\Services\CreateService;
use Modules\Core\Contracts\Services\Service;
use Modules\Core\Contracts\Services\UpdateService;
use Modules\Core\Models\Model;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Events\DealMovedToStage;
use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Stage;

class DealService implements Service, CreateService, UpdateService
{
    public function create(array $attributes): Deal
    {
        // Allow providing status e.q. via API or Zapier on create
        // If not provided, default is open, we need to set it to open
        // so Laravel can fill into the model and later then the status
        // can be used e.q. on workflows, as if we don't fill the status, will be false
        // regardless if $table->unsignedInteger('status')->index()->default(1); is default 1
        $attributes['status'] ??= DealStatus::open;

        if (is_string($attributes['status'])) {
            $attributes['status'] = DealStatus::find($attributes['status']);
        }

        $model = new Deal($attributes);

        $stage = $this->getStage($attributes['stage_id']);

        // When creating deal, if the pipeline is not provided use the stage pipeline_id
        // additionally, we will check if the actual provided pipeline is not equal with the provided stage
        // pipeline, when such case, we will just use the stage pipeline
        if (! isset($attributes['pipeline_id']) || $attributes['pipeline_id'] != $stage->pipeline_id) {
            $model->fill(['pipeline_id' => $stage->pipeline_id]);
        }

        $model->save();

        // We will check if the provided billable has products, if yes, then in this case the user wants to add products
        // however, if no, we won't save the billable as it will update the amount column of the deal to 0 but the user may
        // have entered an amount for this deal when creating
        if (count($attributes['billable']['products'] ?? []) > 0) {
            (new BillableService())->save($attributes['billable'], $model);
        }

        // When new deal is created, always increment the board order so the new
        // deal is pushed on top and all deals are ordered properly
        $model->newQuery()->increment('board_order');

        return $model;
    }

    public function update(Model $model, array $attributes): Deal
    {
        if (array_key_exists('stage_id', $attributes)) {
            $originalStage = $this->getStage($model->stage_id);
        }

        if (is_string($attributes['status'] ?? null)) {
            $attributes['status'] = DealStatus::find($attributes['status']);
        }

        $model->fill($attributes);

        // Update the pipeline_id from the actual stage pipeline if the
        // deal pipeline is not the same as the stage pipeline
        if ($this->pipelineIsNotEqualToStagePipeline($model->pipeline_id, $attributes)) {
            $model->fill(['pipeline_id' => $this->getStage($attributes['stage_id'])->pipeline_id]);
        }

        if (array_key_exists('status', $attributes) && $attributes['status'] != $model->getOriginal('status')) {
            unset($model->status);
            $model->changeStatus($attributes['status'], $attributes['lost_reason'] ?? null);
        }

        $model->save();

        if ($attributes['billable'] ?? null) {
            (new BillableService())->save($attributes['billable'], $model);
        }

        DealMovedToStage::dispatchIf($model->wasChanged('stage_id'), $model, $originalStage ?? null);

        return $model;
    }

    /**
     * Check whether the deal pipeline is equal to the stage pipeline
     * based on the the provided deal and attributes.
     */
    protected function pipelineIsNotEqualToStagePipeline(?int $originalPipelineId, array $attributes): bool
    {
        if (isset($attributes['stage_id']) && isset($attributes['pipeline_id'])) {
            return $this->getStage($attributes['stage_id'])->pipeline_id != $attributes['pipeline_id'];
        }

        if (isset($attributes['stage_id'])) {
            return $this->getStage($attributes['stage_id'])->pipeline_id != $originalPipelineId;
        }

        return false;
    }

    /**
     * Get stage by given ID.
     *
     * Caches results because of import to prevent thousands of queries.
     */
    protected function getStage(int|string $id): Stage
    {
        return Cache::driver('array')->rememberForever(
            'deal-service-stage-'.$id, fn () => Stage::find($id)
        );
    }
}
