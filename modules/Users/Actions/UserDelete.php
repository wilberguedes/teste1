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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Actions\ActionFields;
use Modules\Core\Actions\ActionRequest;
use Modules\Core\Actions\DestroyableAction;
use Modules\Core\Fields\User;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Users\Services\UserService;

class UserDelete extends DestroyableAction
{
    /**
     * Handle method
     *
     * @return mixed
     */
    public function handle(Collection $models, ActionFields $fields)
    {
        // User delete action flag

        $service = new UserService();

        foreach ($models as $model) {
            $service->delete($model, (int) $fields->user_id);
        }
    }

    /**
     * Query the models for execution
     *
     * @param  array  $ids
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findModelsForExecution($ids, Builder $query)
    {
        return $query->with(
            ['personalEmailAccounts', 'oAuthAccounts', 'calendar', 'comments']
        )->findMany($ids);
    }

    /**
     * Get the action fields
     */
    public function fields(ResourceRequest $request): array
    {
        return [
            User::make('')
                ->help(__('users::user.transfer_data_info'))
                ->helpDisplay('text')
                ->rules('required'),
        ];
    }

    /**
     * @param  \Illumindate\Database\Eloquent\Model  $model
     */
    public function authorizedToRun(ActionRequest $request, $model): bool
    {
        return $request->user()->isSuperAdmin();
    }

    /**
     * Action name
     */
    public function name(): string
    {
        return __('users::user.actions.delete');
    }
}
