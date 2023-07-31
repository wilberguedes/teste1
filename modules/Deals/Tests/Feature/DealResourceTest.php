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

use Illuminate\Support\Carbon;
use Modules\Activities\Models\Activity;
use Modules\Billable\Models\Billable;
use Modules\Billable\Models\BillableProduct;
use Modules\Calls\Models\Call;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Models\ModelVisibilityGroup;
use Modules\Core\Resource\Http\ResourceRequest;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\Enums\DealStatus;
use Modules\Deals\Models\Pipeline;
use Modules\Notes\Models\Note;
use Modules\Users\Models\User;

class DealResourceTest extends ResourceTestCase
{
    protected $resourceName = 'deals';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();
        $user = $this->createUser();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->postJson($this->createEndpoint(), [
            'name' => 'Deal Name',
            'expected_close_date' => $closeDate = now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
            'user_id' => $user->id,
            'companies' => [$company->id],
            'contacts' => [$contact->id],
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);
        $response->assertJsonCount(1, 'companies')
            ->assertJsonCount(1, 'contacts')
            ->assertJson([
                'companies' => [['id' => $company->id]],
                'contacts' => [['id' => $contact->id]],
                'name' => 'Deal Name',
                'expected_close_date' => Carbon::parse($closeDate)->toJSON(),
                'pipeline_id' => (string) $pipeline->id,
                'amount' => (string) 1250,
                'stage_id' => (string) $stage->id,
                'user_id' => (string) $user->id,
                'was_recently_created' => true,
                'path' => '/deals/1',
                'display_name' => 'Deal Name',
                'companies_count' => 1,
                'contacts_count' => 1,
            ]);
    }

    public function test_user_can_update_resource_record()
    {
        $user = $this->signIn();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create();
        $record = $this->factory()->has(Company::factory())->create();

        $response = $this->putJson($this->updateEndpoint($record), [
            'name' => 'Deal Name',
            'expected_close_date' => $closeDate = now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 3655,
            'stage_id' => $stage->id,
            'user_id' => $user->id,
            'companies' => [$company->id],
            'contacts' => [$contact->id],
        ])
            ->assertOk();

        $this->assertResourceJsonStructure(($response));

        $response->assertJsonCount(count($this->resource()->resolveActions(app(ResourceRequest::class))), 'actions')
            ->assertJsonCount(1, 'companies')
            ->assertJsonCount(1, 'contacts')
            ->assertJson([
                'companies' => [['id' => $company->id]],
                'contacts' => [['id' => $contact->id]],
                'name' => 'Deal Name',
                'expected_close_date' => Carbon::parse($closeDate)->toJSON(),
                'pipeline_id' => (string) $pipeline->id,
                'amount' => (string) 3655,
                'stage_id' => (string) $stage->id,
                'user_id' => (string) $user->id,
                'path' => '/deals/1',
                'display_name' => 'Deal Name',
                'companies_count' => 1,
                'contacts_count' => 1,
            ]);
    }

    public function test_unauthorized_user_cannot_update_resource_record()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();
        $record = $this->factory()->has(Company::factory())->create();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Deal Name',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
        ])->assertForbidden();
    }

    public function test_authorized_user_can_update_own_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $user = $this->asRegularUser()->withPermissionsTo('edit own deals')->signIn();
        $record = $this->factory()->for($user)->create();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Deal Name',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
        ])->assertOk();
    }

    public function test_authorized_user_can_update_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all deals')->signIn();
        $record = $this->factory()->create();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Deal Name',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
        ])->assertOk();
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

    public function test_user_can_globally_search_deals()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->getJson("/api/search?q={$record->name}")
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path)
            ->assertJsonPath('0.data.0.display_name', $record->display_name);
    }

    public function test_an_unauthorized_user_can_global_search_only_own_records()
    {
        $this->seed(PermissionsSeeder::class);
        $user = $this->asRegularUser()->withPermissionsTo('view own deals')->signIn();
        $user1 = $this->createUser();

        $this->factory()->for($user1)->create(['name' => 'DEAL KONKORD']);
        $record = $this->factory()->for($user)->create(['name' => 'DEAL INOKLAPS']);

        $this->getJson('/api/search?q=DEAL')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path)
            ->assertJsonPath('0.data.0.display_name', $record->display_name);
    }

    public function test_user_can_force_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()
            ->has(Contact::factory())
            ->has(Company::factory())
            ->has(Note::factory())
            ->has(Call::factory())
            ->has(Activity::factory())
            ->create();

        Billable::factory()
            ->withBillableable($record)
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $record->delete();

        $this->deleteJson($this->forceDeleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 0);
        $this->assertDatabaseCount('billables', 0);
    }

    public function test_user_can_soft_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 1);
    }

    public function test_user_can_export_deals()
    {
        $this->performExportTest();
    }

    public function test_user_can_create_resource_record_with_custom_fields()
    {
        $this->signIn();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();

        $response = $this->postJson($this->createEndpoint(), array_merge([
            'name' => 'Deal Name',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
        ], $this->customFieldsPayload()))->assertCreated();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_update_resource_record_with_custom_fields()
    {
        $this->signIn();
        $record = $this->factory()->create();
        $pipeline = Pipeline::factory()->withStages()->create();
        $stage = $pipeline->stages->first();

        $response = $this->putJson($this->updateEndpoint($record), array_merge([
            'name' => 'Deal Name',
            'expected_close_date' => now()->addMonth()->format('Y-m-d'),
            'pipeline_id' => $pipeline->id,
            'amount' => 1250,
            'stage_id' => $stage->id,
        ], $this->customFieldsPayload()))->assertOk();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_import_deals()
    {
        $this->createUser();

        $this->performImportTest();
    }

    public function test_user_can_import_deals_with_custom_fields()
    {
        $this->createUser();

        $this->performImportWithCustomFieldsTest();
    }

    protected function performImportTest()
    {
        Pipeline::factory()->withStages()->create();
        parent::performExportTest();
    }

    protected function performImportWithCustomFieldsTest()
    {
        Pipeline::factory()->withStages()->create();
        parent::performImportWithCustomFieldsTest();
    }

    protected function importEndpoint($import)
    {
        $id = is_int($import) ? $import : $import->getKey();
        $pipeline = Pipeline::first();

        return "/api/{$this->resourceName}/import/{$id}?pipeline_id={$pipeline->id}";
    }

    public function test_user_can_load_the_deals_table()
    {
        $this->performTestTableLoad();
    }

    public function test_deals_table_loads_all_fields()
    {
        $this->performTestTableCanLoadWithAllFields();
    }

    public function test_deals_table_can_be_customized()
    {
        $user = $this->signIn();

        $this->postJson($this->tableEndpoint().'/settings', [
            'maxHeight' => '120px',
            'columns' => [
                ['attribute' => 'created_at', 'order' => 2, 'hidden' => false],
                ['attribute' => 'name', 'order' => 3, 'hidden' => false],
            ],
            'order' => [
                ['attribute' => 'created_at', 'direction' => 'asc'],
                ['attribute' => 'name', 'direction' => 'desc'],
            ],
        ])->assertOk();

        $settings = $this->resource()->resolveTable($this->createRequestForTable($user))->settings();

        $this->assertSame('120px', $settings->maxHeight());
        $this->assertCount(2, $settings->getCustomizedColumns());
        $this->assertCount(2, $settings->getCustomizedOrder());
    }

    public function test_it_doesnt_create_document_with_restricted_visibility_pipeline()
    {
        $this->asRegularUser()->signIn();

        $pipeline = $this->newPipelineFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->postJson(
            $this->createEndpoint(),
            ['pipeline_id' => $pipeline->id]
        )
            ->assertJsonValidationErrors(['pipeline_id' => 'This Pipeline value is forbidden.']);
    }

    public function test_it_doesnt_update_document_with_restricted_visibility_pipeline()
    {
        $this->asRegularUser()->signIn();
        $document = $this->factory()->create();
        $pipeline = $this->newPipelineFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->putJson(
            $this->updateEndpoint($document),
            ['pipeline_id' => $pipeline->id]
        )
            ->assertJsonValidationErrors(['pipeline_id' => 'This Pipeline value is forbidden.']);
    }

    protected function newPipelineFactoryWithVisibilityGroup($group, $attached)
    {
        return Pipeline::factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'amount', 'billable', 'board_order', 'calls_count', 'changelog', 'companies', 'companies_count', 'contacts', 'contacts_count', 'created_at', 'display_name', 'expected_close_date', 'id', 'media', 'name', 'next_activity_date', 'notes_count', 'owner_assigned_date', 'pipeline', 'pipeline_id', 'stage', 'stage_changed_date', 'stage_id', 'status', 'time_in_stages', 'timeline_subject_key', 'incomplete_activities_for_user_count', 'unread_emails_for_user_count', 'updated_at', 'path', 'user', 'user_id', 'was_recently_created', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);

        if ($response->getData()->status == DealStatus::won->name) {
            $response->assertResourceJsonStructure(['won_date']);
        }

        if ($response->getData()->status == DealStatus::lost->name) {
            $response->assertResourceJsonStructure(['lost_date', 'lost_reason']);
        }
    }
}
