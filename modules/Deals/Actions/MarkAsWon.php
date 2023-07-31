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

use Illuminate\Support\Collection;
use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Actions\ActionRequest;

class MarkAsWon extends Action
{
    /**
     * Indicates that the action will be hidden on the view/update view
     */
    public bool $hideOnUpdate = true;

    /**
     * Handle method.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        foreach ($models as $model) {
            $model->markAsWon();
        }

        return parent::confetti();
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
        return __('deals::deal.actions.mark_as_won');
    }
}
