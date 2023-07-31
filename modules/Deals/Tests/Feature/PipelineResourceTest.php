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

namespace Modules\Deals\Tests\Feature;

use Modules\Core\Models\ModelVisibilityGroup;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\Models\Stage;
use Modules\Users\Models\Team;

class PipelineResourceTest extends ResourceTestCase
{
    protected $resourceName = 'pipelines';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), [
            'name' => 'Dummy Pipeline',
            'stages' => [
                [
                    'name' => 'Stage Name',
                    'win_probability' => 100,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJson(['name' => 'Dummy Pipeline'])
            ->assertJsonCount(1, 'stages');
    }

    public function test_user_can_create_resource_record_without_providing_stages()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), [
            'name' => 'Dummy Pipeline',
        ])
            ->assertCreated()
            ->assertJson(['name' => 'Dummy Pipeline'])
            ->assertJsonCount(0, 'stages');
    }

    public function test_unauthorized_user_cannot_create_resource_record()
    {
        $this->asRegularUser()->signIn();

        $this->postJson($this->createEndpoint(), ['name' => 'Dummy Pipeline'])->assertForbidden();
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();
        $stage = Stage::factory()->for($pipeline)->create(['win_probability' => 50]);

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Changed',
            'stages' => [
                [
                    'name' => 'Stage Name',
                    'win_probability' => 100,
                    'display_order' => 1,
                ],
                [
                    'id' => $stage->id,
                    'name' => 'Changed Name',
                    'win_probability' => 25,
                    'display_order' => 2,
                ],
            ],
        ])->assertOk()
            ->assertJsonCount(2, 'stages')
            ->assertJson([
                'stages' => [
                    [
                        'name' => 'Stage Name',
                        'win_probability' => 100,
                        'display_order' => 1,
                    ],
                    [
                        'name' => 'Changed Name',
                        'win_probability' => 25,
                        'display_order' => 2,
                    ],
                ],
            ])
            ->assertJson(['name' => 'Changed']);
    }

    public function test_unauthorized_user_cannot_update_resource_record()
    {
        $this->asRegularUser()->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Changed',
        ])->assertForbidden();
    }

    public function test_user_can_retrieve_resource_records()
    {
        $this->signIn();

        $this->factory()->count(5)->create();

        $this->getJson($this->indexEndpoint())->assertJsonCount(5, 'data');
    }

    public function test_user_can_retrieve_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson($this->showEndpoint($record))->assertOk();
    }

    public function test_user_can_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
    }

    public function test_unauthorized_user_cannot_delete_resource_record()
    {
        $this->asRegularUser()->signIn();

        $pipeline = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($pipeline))->assertForbidden();
    }

    public function test_pipeline_requires_name()
    {
        $this->signIn();

        $this->postJson($this->createEndpoint(), ['name' => ''])->assertJsonValidationErrors(['name']);

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline))->assertJsonValidationErrors(['name']);
    }

    public function test_pipeline_update_requires_not_empty_stages()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), ['name' => 'Pipeline', 'stages' => []])
            ->assertJsonValidationErrorFor('stages');
    }

    public function test_pipeline_name_must_be_unique()
    {
        $this->signIn();

        $pipelines = $this->factory()->count(2)->create();

        $this->postJson(
            $this->createEndpoint(),
            ['name' => $pipelines->first()->name,
            ]
        )->assertJsonValidationErrors(['name']);

        $this->putJson(
            $this->updateEndpoint($pipelines->get(1)),
            ['name' => $pipelines->first()->name]
        )->assertJsonValidationErrors(['name']);
    }

    public function test_pipeline_update_requires_stages_name_to_be_distinct()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Pipeline',
            'stages' => [
                [
                    'name' => 'Duplicate Stage',
                ],
                [
                    'name' => 'Duplicate Stage',
                ],
                [
                    'name' => 'Unique Stage',
                ],
            ],
        ])
            ->assertJsonValidationErrorFor('stages.0.name')
            ->assertJsonValidationErrorFor('stages.1.name')
            ->assertJsonMissingValidationErrors('stages.2.name');
    }

    public function test_pipeline_update_requires_all_stages_to_have_a_name()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Pipeline',
            'stages' => [
                [
                    'name' => '',
                ],
                [
                    'name' => 'Sample Stage',
                ],
            ],
        ])
            ->assertJsonValidationErrorFor('stages.0.name');
    }

    public function test_pipeline_update_requires_all_stages_to_have_win_probability_defined()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Pipeline',
            'stages' => [
                [
                    'name' => 'Sample Stage',
                    'win_probability' => 50,
                ],
                [
                    'name' => 'Sample Stage',
                ],
            ],
        ])
            ->assertJsonValidationErrorFor('stages.1.win_probability');
    }

    public function test_pipeline_update_requires_stages_win_probability_to_be_max_100()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Pipeline',
            'stages' => [
                [
                    'name' => 'Sample Stage',
                    'win_probability' => 125,
                ],
                [
                    'name' => 'Sample Stage',
                    'win_probability' => 30,
                ],
            ],
        ])
            ->assertJsonValidationErrorFor('stages.0.win_probability')
            ->assertJsonMissingValidationErrors('stages.1.win_probability');
    }

    public function test_pipeline_update_requires_stages_win_probability_to_be_min_0()
    {
        $this->signIn();

        $pipeline = $this->factory()->create();

        $this->putJson($this->updateEndpoint($pipeline), [
            'name' => 'Pipeline',
            'stages' => [
                [
                    'name' => 'Sample Stage',
                    'win_probability' => -5,
                ],
                [
                    'name' => 'Sample Stage',
                    'win_probability' => 30,
                ],
            ],
        ])
            ->assertJsonValidationErrorFor('stages.0.win_probability')
            ->assertJsonMissingValidationErrors('stages.1.win_probability');
    }

    public function test_admin_user_can_see_all_pipelines()
    {
        $user = $this->signIn();
        $pipeline = $this->newPipelineFactoryWithVisibilityGroup('teams', Team::factory())->create();

        $this->assertTrue($pipeline->isVisible($user));
        $this->getJson($this->indexEndpoint())->assertJsonCount(1, 'data');
    }

    protected function newPipelineFactoryWithVisibilityGroup($group, $attached)
    {
        return $this->factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }
}
