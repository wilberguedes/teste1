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

namespace Modules\Core\Tests\Unit\Resources;

use Modules\Core\Facades\Innoclapps;
use Tests\Fixtures\EventResource;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    public function test_it_can_find_resource_by_model()
    {
        $this->assertNotNull(Innoclapps::resourceByModel(EventResource::$model));
        $this->assertNotNull(Innoclapps::resourceByModel(resolve(EventResource::$model)));
    }

    public function test_it_can_find_globally_searchable_resources()
    {
        $this->assertNotNull(Innoclapps::globallySearchableResources()->first(function ($resource) {
            return $resource->name() === 'events';
        }));
    }
}
