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

namespace Modules\Calls\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasCalls
{
    /**
     * Get all of the calls for the model.
     */
    public function calls(): MorphToMany
    {
        return $this->morphToMany(\Modules\Calls\Models\Call::class, 'callable');
    }
}
