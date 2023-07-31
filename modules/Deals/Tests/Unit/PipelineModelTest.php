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

namespace Modules\Deals\Tests\Unit;

use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Pipeline;
use Tests\TestCase;

class PipelineModelTest extends TestCase
{
    public function test_pipeline_can_be_primary()
    {
        $pipeline = Pipeline::factory()->primary()->create();

        $this->assertTrue($pipeline->isPrimary());
    }

    public function test_pipeline_has_deals()
    {
        $pipeline = Pipeline::factory()->withStages()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $pipeline->deals);
    }
}
