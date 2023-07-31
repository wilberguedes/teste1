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

namespace Modules\Deals\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Actions\ActionRequest;
use Modules\Core\Fields\Select;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Deals\Models\Stage;
use Modules\Deals\Services\DealService;

class ChangeDealStage extends Action
{
    /**
     * Indicates that the action will be hidden on the update view.
     */
    public bool $hideOnUpdate = true;

    /**
     * Handle method.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        $service = new DealService();

        foreach ($models as $model) {
            $service->update($model, ['stage_id' => $fields->stage_id]);
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Select::make('stage_id', __('deals::fields.deals.stage.name'))
                ->labelKey('name')
                ->valueKey('id')
                ->rules('required')
                ->options(function () use ($request) {
                    return Stage::allStagesForOptions($request->user());
                }),
        ];
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('update', $model);
    }

    /**
     * Query the models for execution
     *
     * @param  array  $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findModelsForExecution($ids, Builder $query)
    {
        return $query->with('stage')->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('deals::deal.actions.change_stage');
    }
}
