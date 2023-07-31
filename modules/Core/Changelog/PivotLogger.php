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

namespace Modules\Core\Changelog;

use Modules\Core\Contracts\Presentable;
use Modules\Core\Facades\ChangeLogger;

class PivotLogger
{
    /**
     * Perform the pivot activity log
     *
     * @param  \Illuminate\Database\Eloquent\Model  $actionOn The action attached|detached is performed to
     * @param  \Modules\Core\Contracts\Presentable  $pivotModel The pivot model
     * @param  string  $relation
     * @param  string  $action attached|detached
     * @return mixed
     */
    protected static function perform($actionOn, Presentable $pivotModel, $relation, $action)
    {
        return ChangeLogger::onModel($actionOn, [
            'id' => $pivotModel->getKey(),
            'name' => $pivotModel->display_name,
            'path' => $pivotModel->path,
        ])
            ->identifier($action)
            // Always add on second to the created_at as usually the log is performed
            // after a record is created and they won't be displayed properly on the timeline
            // e.q. the associated will be first, then created
            ->createdAt(now()->addSecond(1))
            ->log();
    }

    /**
     * Get the related pivot models by the pivot id's
     *
     * @param  \Modules\Core\Models\Model  $model
     * @param  array  $pivotIds
     * @param  string  $relation The main model relation
     * @return Collection
     */
    public static function getRelatedPivotIds($model, $pivotIds, $relation)
    {
        $query = $model->{$relation}()->getModel()->query();

        if ($query->getModel()->usesSoftDeletes()) {
            $query->withTrashed();
        }

        return $query->whereIn($model->getKeyName(), $pivotIds)->get();
    }
}
