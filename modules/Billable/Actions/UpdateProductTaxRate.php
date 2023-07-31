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

namespace Modules\Billable\Actions;

use Illuminate\Support\Collection;
use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Actions\ActionRequest;
use Modules\Core\Fields\Numeric;
use Modules\Core\Resource\Http\ResourceRequest;

class UpdateProductTaxRate extends Action
{
    /**
     * Handle method.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        foreach ($models as $model) {
            $rate = $fields->tax_rate;

            $model->fill([
                'tax_rate' => (is_int($rate) || is_float($rate) || (is_numeric($rate) && ! empty($rate))) ? $rate : 0,
            ])->save();
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            Numeric::make('tax_rate', __('billable::product.tax_rate'))
                ->inputGroupAppend('%')
                ->withMeta(['attributes' => ['max' => 100]])
                ->precision(3),
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
     * Action name.
     */
    public function name(): string
    {
        return __('billable::product.actions.update_tax_rate');
    }
}
