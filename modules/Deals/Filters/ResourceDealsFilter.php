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

namespace Modules\Deals\Filters;

use Modules\Core\Filters\Date;
use Modules\Core\Filters\HasMany;
use Modules\Core\Filters\Number;
use Modules\Core\Filters\Numeric;
use Modules\Core\Filters\Operand;

class ResourceDealsFilter extends HasMany
{
    /**
     * Initialize ResourceDealsFilter class
     *
     * @param  string  $singularLabel
     */
    public function __construct($singularLabel)
    {
        parent::__construct('deals', __('deals::deal.deals'));

        $this->setOperands([
            Operand::make('amount', __('deals::deal.deal_amount'))->filter(
                Numeric::make('amount')
            ),
            Operand::make('expected_close_date', __('deals::deal.deal_expected_close_date'))->filter(
                Date::make('expected_close_date')
            ),
            Operand::make('open_count', __('deals::deal.count.open', ['resource' => $singularLabel]))->filter(
                Number::make('open_count')->countableRelation('authorizedOpenDeals')
            ),
            Operand::make('won_count', __('deals::deal.count.won', ['resource' => $singularLabel]))->filter(
                Number::make('won_count')->countableRelation('authorizedWonDeals')
            ),
            Operand::make('lost_count', __('deals::deal.count.lost', ['resource' => $singularLabel]))->filter(
                Number::make('lost_count')->countableRelation('authorizedLostDeals')
            ),
            Operand::make('closed_count', __('deals::deal.count.closed', ['resource' => $singularLabel]))->filter(
                Number::make('closed_count')->countableRelation('authorizedClosedDeals')
            ),
        ]);
    }
}
