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

namespace Modules\Core\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ForceDeleteAction extends Action
{
    /**
     * Handle method.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        foreach ($models as $model) {
            $model->forceDelete();
        }
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->can('delete', $model);
    }

    /**
     * Query the models for execution.
     *
     * @param  array  $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findModelsForExecution($ids, Builder $query)
    {
        return $query->withTrashed()->findMany($ids);
    }

    /**
     * Action name.
     */
    public function name(): string
    {
        return __('core::app.soft_deletes.force_delete');
    }

    /**
     * toArray.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['destroyable' => true]);
    }
}
