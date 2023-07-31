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

namespace Modules\Users\Services;

use Modules\Core\Models\Filter;
use Modules\Core\Models\Workflow;
use Modules\Users\Events\TransferringUserData;
use Modules\Users\Models\Team;
use ReflectionClass;
use ReflectionMethod;

class TransferUserDataService
{
    /**
     * Create new TransferUserData instance.
     */
    public function __construct(protected int $toUserId, protected int $fromUserId)
    {
    }

    /**
     * Invoke the transfer
     */
    public function __invoke(): void
    {
        TransferringUserData::dispatch($this->toUserId, $this->fromUserId);

        $methods = (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (! str_starts_with($method->getName(), '__')) {
                $this->{$method->getName()}();
            }
        }
    }

    /**
     * Transfer shared filter
     */
    public function filters(): void
    {
        Filter::where('user_id', $this->fromUserId)->shared()->update(['user_id' => $this->toUserId]);
    }

    /**
     * Transfer created workflows
     */
    public function workflows(): void
    {
        Workflow::where('created_by', $this->fromUserId)->update(['created_by' => $this->toUserId]);
    }

    /**
     * Transfer teams
     */
    public function teams(): void
    {
        Team::where('user_id', $this->fromUserId)->update(['user_id' => $this->toUserId]);
    }
}
