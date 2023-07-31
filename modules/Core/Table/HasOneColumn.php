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

namespace Modules\Core\Table;

use Illuminate\Database\Eloquent\Builder;

class HasOneColumn extends RelationshipColumn
{
    /**
     * Apply the order by query for the column
     */
    public function orderBy(Builder $query, array $order): Builder
    {
        $relation = $this->relationName;
        $relationTable = $query->getModel()->{$relation}()->getModel()->getTable();

        $query->leftJoin($relationTable, function ($join) use ($query, $relation) {
            $join->on(
                $query->getModel()->getQualifiedKeyName(),
                '=',
                $query->getModel()->{$relation}()->getQualifiedForeignKeyName()
            );
        });

        ['attribute' => $attribute, 'direction' => $direction] = $order;

        if (is_callable($this->orderByUsing)) {
            return call_user_func_array($this->orderByUsing, [$query, $attribute, $direction, $this]);
        }

        return $query->orderBy($this->relationField, $direction);
    }
}
