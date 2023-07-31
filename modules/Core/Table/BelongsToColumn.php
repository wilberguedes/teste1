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
use Illuminate\Support\Str;

class BelongsToColumn extends RelationshipColumn
{
    /**
     * Apply the order by query for the column
     */
    public function orderBy(Builder $query, array $order): Builder
    {
        $relation = $this->relationName;
        $instance = $query->getModel()->{$relation}();
        $table = $instance->getModel()->getTable();

        $alias = Str::snake(class_basename($query->getModel())).'_'.$relation.'_'.$table;

        $query->leftJoin(
            $table.' as '.$alias,
            fn ($join) => $join->on($instance->getQualifiedForeignKeyName(), '=', $alias.'.id')
        );

        ['attribute' => $attribute, 'direction' => $direction] = $order;

        if (is_callable($this->orderByUsing)) {
            return call_user_func_array($this->orderByUsing, [$query, $attribute, $direction, $alias, $this]);
        }

        return $query->orderBy(
            $alias.'.'.$this->relationField, $direction
        );
    }
}
