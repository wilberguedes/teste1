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

namespace Modules\Deals\Tests\Feature\Actions;

use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\Actions\ChangeDealStage;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;

class ChangeDealStageTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'deals';

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ChangeDealStage;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_change_deal_stage_action()
    {
        $this->signIn();
        $pipeline = Pipeline::factory()->withStages()->create();
        $deal = $this->factory()->for($pipeline)->for($pipeline->stages->get(1))->create();
        $stage = $pipeline->stages->get(0);

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => $stage->id,
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertEquals($stage->id, $deal->fresh()->stage_id);
    }

    public function test_authorized_user_can_run_change_deal_stage_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all deals')->signIn();
        $user = $this->createUser();
        $pipeline = Pipeline::factory()->withStages()->create();
        $deal = $this->factory()->for($pipeline)->for($pipeline->stages->get(1))->for($user)->create();
        $stage = $pipeline->stages->get(0);

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => $stage->id,
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertEquals($stage->id, $deal->fresh()->stage_id);
    }

    public function test_unauthorized_user_can_run_change_deal_stage_action_on_own_deal()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own deals')->signIn();
        $this->createUser();

        $pipeline = Pipeline::factory()->withStages()->create();
        $dealForSignedIn = $this->factory()->for($pipeline)->for($pipeline->stages->get(1))->for($signedInUser)->create();
        $stage = $pipeline->stages->get(0);
        $otherDeal = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => $stage->id,
            'ids' => [$otherDeal->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => $stage->id,
            'ids' => [$dealForSignedIn->id],
        ]);

        $this->assertEquals($stage->id, $dealForSignedIn->fresh()->stage_id);
    }

    public function test_change_deal_stage_action_requires_stage()
    {
        $this->signIn();
        $this->createUser();
        $activity = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => '',
            'ids' => [$activity->id],
        ])->assertJsonValidationErrors('stage_id');
    }

    public function test_it_updates_the_pipeline_id_if_the_provided_stage_does_not_belongs_to_the_current_deal_pipeline()
    {
        $this->signIn();
        $deal = $this->factory()->create();
        $stage = Stage::factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'stage_id' => $stage->id,
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertEquals($deal->fresh()->pipeline_id, $stage->pipeline_id);
    }
}
