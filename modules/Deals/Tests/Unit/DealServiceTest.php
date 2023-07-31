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

use Illuminate\Support\Facades\Event;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Events\DealMovedToStage;
use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;
use Modules\Deals\Services\DealService;
use Tests\TestCase;

class DealServiceTest extends TestCase
{
    public function test_when_creating_it_uses_stage_pipeline_when_pipeline_is_not_provided()
    {
        $deal = (new DealService())->create([
            'name' => 'Deal Name',
            'stage_id' => Stage::factory()->create()->id,
        ]);

        $this->assertEquals($deal->stage->pipeline_id, $deal->pipeline_id);
    }

    public function test_status_can_be_provided_when_creating_new_deal()
    {
        $deal = (new DealService())->create([
            'name' => 'Deal Name',
            'stage_id' => Stage::factory()->create()->id,
            'status' => DealStatus::lost,
        ]);

        $this->assertEquals(DealStatus::lost, $deal->status);

        // string
        $deal = (new DealService())->create([
            'name' => 'Deal Name',
            'stage_id' => Stage::factory()->create()->id,
            'status' => DealStatus::won->name,
        ]);

        $this->assertEquals(DealStatus::won, $deal->status);
    }

    public function test_status_can_be_provided_when_updating_a_deal()
    {
        $deal = Deal::factory()->open()->create();

        $updated = (new DealService())->update($deal, [
            'status' => DealStatus::lost,
        ]);

        $this->assertEquals(DealStatus::lost, $updated->status);

        // String
        $updated = (new DealService())->update($deal, [
            'status' => DealStatus::won->name,
        ]);

        $this->assertEquals(DealStatus::won, $updated->status);
    }

    public function test_when_updating_it_uses_stage_pipeline_when_pipeline_is_not_provided()
    {
        $pipeline = Pipeline::factory()->has(Stage::factory())->create();

        $deal = Deal::factory([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $pipeline->stages[0]->id,
        ])->create();

        $updated = (new DealService())->update($deal, [
            'pipeline_id' => null,
            'stage_id' => $deal->stage_id,
        ]);

        $this->assertEquals($updated->stage->pipeline_id, $updated->pipeline_id);
    }

    public function test_when_creating_it_uses_stage_pipeline_when_provided_pipeline_id_does_not_belong_to_the_stage()
    {
        $otherPipeline = Pipeline::factory()->create();
        $mainPipeline = Pipeline::factory()->has(Stage::factory())->create();

        $deal = (new DealService())->create([
            'name' => 'Deal Name',
            'pipeline_id' => $otherPipeline->id,
            'stage_id' => $mainPipeline->stages[0]->id,
        ]);

        $this->assertEquals($deal->stage->pipeline_id, $deal->pipeline_id);
    }

    public function test_when_updating_it_uses_stage_pipeline_id_when_provided_pipeline_id_does_not_belong_to_the_stage()
    {
        $otherPipeline = Pipeline::factory()->create();
        $deal = Deal::factory()->for(Pipeline::factory()->has(Stage::factory()))->create();

        $updated = (new DealService())->update($deal, [
            'pipeline_id' => $otherPipeline->id,
            'stage_id' => $deal->pipeline->stages[0]->id,
        ]);

        $this->assertEquals($updated->stage->pipeline_id, $updated->pipeline_id);
    }

    public function test_moved_to_stage_event_is_triggered_when_deal_stage_is_updated()
    {
        $deal = Deal::factory()->create();
        $stageId = Stage::where('id', '!=', $deal->stage_id)->first()->id;

        Event::fake();
        (new DealService())->update($deal, ['stage_id' => $stageId]);

        Event::assertDispatched(DealMovedToStage::class);
    }

    public function test_stage_moved_activity_is_logged_when_deal_stage_is_updated()
    {
        $deal = Deal::factory()->create();
        $stageId = Stage::where('id', '!=', $deal->stage_id)->first()->id;

        (new DealService())->update($deal, ['stage_id' => $stageId]);

        $latestActivity = $deal->changelog()->orderBy('id', 'desc')->first();
        $this->assertStringContainsString('deals::deal.timeline.stage.moved', (string) $latestActivity->properties);
    }
}
