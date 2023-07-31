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

namespace Modules\Users\Actions;

use Illuminate\Support\Collection;
use Modules\Core\Actions\Action;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Actions\ActionRequest;
use Modules\Core\Fields\User;
use Modules\Core\Resource\Http\ResourceRequest;

class AssignOwnerAction extends Action
{
    /**
     * Handle method.
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        foreach ($models as $model) {
            $model->forceFill([
                $model->user()->getForeignKeyName() => $fields->user_id,
            ])->save();
        }
    }

    /**
     * Get the action fields.
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            User::make(__('users::user.user'))
                ->rules('required')
                ->withMeta(['attributes' => ['clearable' => false]]),
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
        return __('users::user.assign');
    }
}
