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

namespace Modules\Deals\Fields;

use Modules\Core\Fields\MorphToMany;
use Modules\Core\Table\MorphToManyColumn;
use Modules\Deals\Http\Resources\DealResource;

class Deals extends MorphToMany
{
    /**
     * Field order
     *
     * @var int
     */
    public ?int $order = 999;

    /**
     * Create new instance of Deals field
     *
     * @param  string  $relation
     * @param  string  $label Custom label
     */
    public function __construct($relation = 'deals', $label = null)
    {
        parent::__construct($relation, $label ?? __('deals::deal.deals'));

        $this->setJsonResource(DealResource::class)
            ->labelKey('name')
            ->valueKey('id')
            ->excludeFromExport()
            ->excludeFromImport()
            ->tapIndexColumn(function (MorphToManyColumn $column) {
                if (! $this->counts()) {
                    $column->useComponent('table-data-presentable-column');
                }
            })
            ->excludeFromZapierResponse()
            ->async('/deals/search')
            ->lazyLoad('/deals', ['order' => 'created_at|desc'])
            ->provideSampleValueUsing(fn () => [1, 2]);
    }
}
