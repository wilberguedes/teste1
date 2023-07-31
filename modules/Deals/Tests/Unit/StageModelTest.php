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
use Modules\Deals\Models\Stage;
use Tests\TestCase;

class StageModelTest extends TestCase
{
    public function test_stage_has_deals()
    {
        $stage = Stage::factory()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $stage->deals);
    }

    public function test_stage_has_pipeline()
    {
        $stage = Stage::factory()->for(Pipeline::factory())->create();

        $this->assertInstanceOf(Pipeline::class, $stage->pipeline);
    }

    public function test_it_can_properly_retrieve_all_stages_for_option_fields()
    {
        $user = $this->signIn();

        Stage::factory()->count(5)->create();

        $options = Stage::allStagesForOptions($user);

        $this->assertCount(5, $options);
        $this->assertArrayHasKey('id', $options[0]);
        $this->assertArrayHasKey('name', $options[0]);
    }

    public function test_it_cannot_delete_stage_with_deals()
    {
        $stage = Stage::factory()->has(Deal::factory()->count(2))->create();

        $this->expectExceptionMessage(__('deals::deal.stage.delete_usage_warning'));

        $stage->delete();
    }
}
