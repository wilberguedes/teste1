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

use Modules\Core\Models\ModelVisibilityGroup;
use Modules\Deals\Models\Deal;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Services\PipelineService;
use Modules\Users\Models\Team;
use Modules\Users\Models\User;
use Tests\TestCase;

class PipelineServiceTest extends TestCase
{
    public function test_stages_are_created_when_creating_new_pipeline()
    {
        $attributes = Pipeline::factory()->make()->toArray();

        $pipeline = (new PipelineService())->create(array_merge($attributes, [
            'stages' => [
                ['name' => 'Stage 1', 'win_probability' => 20, 'display_order' => 1],
                ['name' => 'Stage 2', 'win_probability' => 100, 'display_order' => 2],
            ],
        ]));

        $this->assertCount(2, $pipeline->stages);
        $this->assertEquals('Stage 1', $pipeline->stages[0]->name);
        $this->assertEquals('Stage 2', $pipeline->stages[1]->name);
    }

    public function test_it_uses_index_as_display_order_when_display_order_is_not_provided()
    {
        // create
        $attributes = Pipeline::factory()->make()->toArray();
        $pipeline = (new PipelineService())->create(array_merge($attributes, [
            'stages' => [
                ['name' => 'Stage 1', 'win_probability' => 20, 'display_order' => 1],
                ['name' => 'Stage 2', 'win_probability' => 100],
            ],
        ]));

        $this->assertEquals(2, $pipeline->stages[1]->display_order);

        // update
        $pipeline = Pipeline::factory()->withStages([
            ['name' => 'Stage 1', 'win_probability' => 20, 'display_order' => 2],
        ])->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'name' => $pipeline->name,
            'stages' => [
                [
                    'id' => $pipeline->stages[0]->id,
                    'name' => 'Stage 1',
                    'win_probability' => 20,
                ],
            ],
        ]);

        $pipeline->load('stages');

        $this->assertEquals(1, $pipeline->stages[0]->display_order);
    }

    public function test_stages_can_be_updated_when_updating_pipeline()
    {
        $pipeline = Pipeline::factory()->withStages([
            ['name' => 'Stage 1', 'win_probability' => 20, 'display_order' => 1],
            ['name' => 'Stage 2', 'win_probability' => 100, 'display_order' => 2],
        ])->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'name' => $pipeline->name,
            'stages' => [
                [
                    'id' => $pipeline->stages[0]->id,
                    'name' => 'Changed name 1',
                    'win_probability' => 40,
                    'display_order' => 1,
                ],
                [
                    'id' => $pipeline->stages[1]->id,
                    'name' => 'Changed name 2',
                    'win_probability' => 80,
                    'display_order' => 2,
                ],
            ],
        ]);

        $pipeline->load('stages');

        $this->assertEquals(40, $pipeline->stages[0]->win_probability);
        $this->assertEquals(80, $pipeline->stages[1]->win_probability);

        $this->assertEquals('Changed name 1', $pipeline->stages[0]->name);
        $this->assertEquals('Changed name 2', $pipeline->stages[1]->name);
    }

    public function test_new_stage_is_created_when_id_is_not_provided()
    {
        $pipeline = Pipeline::factory()->withStages([
            ['name' => 'Stage 1', 'win_probability' => 20, 'display_order' => 1],
        ])->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'name' => $pipeline->name,
            'stages' => [
                [
                    'id' => $pipeline->stages[0]->id,
                    'name' => 'Stage 1',
                    'win_probability' => 20,
                    'display_order' => 1,
                ],
                [
                    'name' => 'Stage 2',
                    'win_probability' => 80,
                    'display_order' => 2,
                ],
            ],
        ]);

        $pipeline->load('stages');

        $this->assertCount(2, $pipeline->stages);
        $this->assertEquals(80, $pipeline->stages[1]->win_probability);
        $this->assertEquals('Stage 2', $pipeline->stages[1]->name);
    }

    public function test_it_cannot_delete_primary_pipeline()
    {
        $pipeline = Pipeline::factory()->primary()->create();

        $this->expectExceptionMessage(__('deals::deal.pipeline.delete_primary_warning'));

        $pipeline->delete();
    }

    public function test_it_cannot_delete_pipeline_with_deals()
    {
        $pipeline = Pipeline::factory()->withStages()->has(Deal::factory()->count(2))->create();

        $this->expectExceptionMessage(__('deals::deal.pipeline.delete_usage_warning_deals'));

        $pipeline->delete();
    }

    public function test_a_pipeline_with_visibility_group_teams_can_be_created()
    {
        $attributes = Pipeline::factory()->make()->toArray();
        $team = Team::factory()->create();

        $pipeline = (new PipelineService())->create(array_merge($attributes, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeTeams,
                'depends_on' => [$team->id],
            ],
        ]));

        $this->assertNotNull($pipeline->visibilityGroup);
        $this->assertCount(1, $pipeline->visibilityGroup->teams);
        $this->assertEquals(Pipeline::$visibilityTypeTeams, $pipeline->visibilityGroup->type);
    }

    public function test_a_pipeline_with_visibility_group_users_can_be_created()
    {
        $attributes = Pipeline::factory()->make()->toArray();
        $user = User::factory()->create();

        $pipeline = (new PipelineService())->create(array_merge($attributes, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeUsers,
                'depends_on' => [$user->id],
            ],
        ]));

        $this->assertNotNull($pipeline->visibilityGroup);
        $this->assertCount(1, $pipeline->visibilityGroup->users);
        $this->assertEquals(Pipeline::$visibilityTypeUsers, $pipeline->visibilityGroup->type);
    }

    public function test_a_pipeline_with_visibility_group_users_can_be_updated()
    {
        $pipeline = Pipeline::factory()
            ->has(
                ModelVisibilityGroup::factory()->users()->hasAttached(User::factory()),
                'visibilityGroup'
            )
            ->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeTeams,
                'depends_on' => [Team::factory()->create()->id],
            ],
        ], );

        $this->assertCount(1, $pipeline->visibilityGroup->teams);
        $this->assertCount(0, $pipeline->visibilityGroup->users);
        $this->assertEquals(Pipeline::$visibilityTypeTeams, $pipeline->visibilityGroup->type);
    }

    public function test_a_pipeline_with_visibility_group_teams_can_be_updated()
    {
        $pipeline = Pipeline::factory()
            ->has(
                ModelVisibilityGroup::factory()->teams()->hasAttached(Team::factory()),
                'visibilityGroup'
            )
            ->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeUsers,
                'depends_on' => [User::factory()->create()->id],
            ],
        ]);

        $this->assertCount(0, $pipeline->visibilityGroup->teams);
        $this->assertCount(1, $pipeline->visibilityGroup->users);
        $this->assertEquals(Pipeline::$visibilityTypeUsers, $pipeline->visibilityGroup->type);
    }

    public function test_it_detaches_all_visibility_dependends_when_visibilty_type_is_set_to_all()
    {
        $pipeline = Pipeline::factory()
            ->has(
                ModelVisibilityGroup::factory()->teams()->hasAttached(Team::factory()->count(2)),
                'visibilityGroup'
            )
            ->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeAll,
                'depends_on' => [],
            ],
        ]);

        $this->assertCount(0, $pipeline->visibilityGroup->teams);
        $this->assertCount(0, $pipeline->visibilityGroup->users);
        $this->assertEquals(Pipeline::$visibilityTypeAll, $pipeline->visibilityGroup->type);
    }

    public function test_cannot_set_primary_pipeline_visibility()
    {
        $pipeline = Pipeline::factory()->primary()->create();

        $pipeline = (new PipelineService())->update($pipeline, [
            'visibility_group' => [
                'type' => Pipeline::$visibilityTypeUsers,
                'depends_on' => [],
            ],
        ]);

        $this->assertNull($pipeline->visibilityGroup);
    }
}
