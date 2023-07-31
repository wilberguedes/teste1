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

namespace Modules\Billable\Filters;

use Modules\Core\Filters\HasMany;
use Modules\Core\Filters\Number;
use Modules\Core\Filters\Operand;
use Modules\Core\Filters\Text;
use Modules\Core\QueryBuilder\Parser;

class BillableProductsFilter extends HasMany
{
    /**
     * Initialize BillableProductsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct()
    {
        parent::__construct('products', __('billable::product.products'));

        $this->setOperands([
            Operand::make('total_count', __('billable::product.total_products'))->filter(
                Number::make('total_count')->countableRelation('products')
            ),
            Operand::make('name', __('billable::product.product_name'))->filter(
                Text::make('name')
            ),
            Operand::make('qty', __('billable::product.product_quantity'))->filter(
                Number::make('qty')
            ),
            Operand::make('unit', __('billable::product.product_unit'))->filter(
                Text::make('unit')
            ),
            Operand::make('sku', __('billable::product.product_sku'))->filter(
                Text::make('sku')->query(function ($builder, $value, $condition, $sqlOperator, $rule, Parser $parser) {
                    return $builder->whereHas(
                        'originalProduct',
                        function ($query) use ($value, $parser, $rule, $condition, $sqlOperator) {
                            return $parser->convertToQuery($query, $rule, $value, $sqlOperator['operator'], $condition);
                        }
                    );
                })
            ),
        ]);
    }
}
