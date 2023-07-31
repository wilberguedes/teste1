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

use Modules\Core\Contracts\Services\CreateService;
use Modules\Core\Contracts\Services\Service;
use Modules\Core\Contracts\Services\UpdateService;
use Modules\Core\Models\Model;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;

class PipelineService implements Service, CreateService, UpdateService
{
    public function create(array $attributes): Pipeline
    {
        $model = Pipeline::create($attributes);

        if (! $model->isPrimary()) {
            $model->saveVisibilityGroup($attributes['visibility_group'] ?? []);
        }

        foreach ($attributes['stages'] ?? [] as $key => $stage) {
            $model->stages()->create(array_merge($stage, [
                'display_order' => $stage['display_order'] ?? $key + 1,
            ]));
        }

        return $model;
    }

    public function update(Model $model, array $attributes): Pipeline
    {
        $model->fill($attributes)->save();

        if (! $model->isPrimary() && ($attributes['visibility_group'] ?? null)) {
            $model->saveVisibilityGroup($attributes['visibility_group']);
        }

        $this->persistStages($model, $attributes['stages'] ?? []);

        return $model;
    }

    /**
     * Update the given stages for the given pipeline.
     */
    protected function persistStages(Pipeline $pipeline, array $stages): void
    {
        foreach ($stages as $key => $stage) {
            $stage['display_order'] = $stage['display_order'] ?? $key + 1;

            if (! isset($stage['id'])) {
                Stage::create([...$stage, ...['pipeline_id' => $pipeline->id]]);

                continue;
            }

            // We will check if there is a stage with the same name before performing an update
            // when a stage is found, this means the the user re-named the stages instead of re-ordering them
            // for example, create 2 stages "Stage" and "Stage 1", save
            // rename "Stage" to "Stage 1" and "Stage 1" to "Stage" and save, it will fail because of the unique foreign key
            // as the "Stage" is saved first but exists in the database with the same name with different ID, as this
            // stage is not yet updated as it comes later in the loop, in this case, will just add a random name to the confliected
            // stage and later the correct name will be set when the stage comes in the loop.
            $stageModelByName = Stage::where([
                'name' => $stage['name'],
                'pipeline_id' => $pipeline->id,
            ])->first();

            $stageModel = Stage::find($stage['id']);

            if ($stageModelByName?->isNot($stageModel)) {
                Stage::withoutTimestamps(fn () => $stageModelByName->fill(['name' => uniqid()])->save());
            }

            $stageModel->fill($stage)->save();
        }
    }
}
