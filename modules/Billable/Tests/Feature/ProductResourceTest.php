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

namespace Modules\Billable\Tests\Feature;

use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Tests\ResourceTestCase;

class ProductResourceTest extends ResourceTestCase
{
    protected $resourceName = 'products';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $response = $this->postJson($this->createEndpoint(), $payload = [
            'name' => 'Macbook Pro',
            'description' => 'INTEL',
            'direct_cost' => 1250,
            'unit_price' => 1500,
            'is_active' => true,
            'sku' => 'MP-2018',
            'tax_label' => 'DDV',
            'tax_rate' => 18,
            'unit' => 'kg',
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJson($payload)
            ->assertJson([
                'was_recently_created' => true,
                'path' => '/products/1',
                'display_name' => 'Macbook Pro',
            ]);
    }

    public function test_user_can_update_resource_record()
    {
        $this->signIn();
        $record = $this->factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), $payload = [
            'name' => 'Macbook Air',
            'description' => 'INTEL',
            'direct_cost' => 1250,
            'unit_price' => 1500,
            'is_active' => false,
            'sku' => 'MP-2018',
            'tax_label' => 'DDV',
            'tax_rate' => 18,
            'unit' => 'kg',
        ])
            ->assertOk();

        $this->assertResourceJsonStructure($response);

        $response->assertJson($payload)
            ->assertJson([
                'path' => '/products/1',
                'display_name' => 'Macbook Air',
            ]);
    }

    public function test_unauthorized_user_cannot_update_resource_record()
    {
        $this->asRegularUser()->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Macbook Air',
            'unit_price' => 1500,
        ])->assertForbidden();
    }

    public function test_authorized_user_can_update_own_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $user = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();
        $record = $this->factory()->for($user, 'creator')->create();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Macbook Air',
            'unit_price' => 1500,
        ])->assertOk();
    }

    public function test_authorized_user_can_update_resource_record()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();
        $record = $this->factory()->create();

        $this->putJson($this->updateEndpoint($record), [
            'name' => 'Macbook Air',
            'unit_price' => 1500,
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

    public function test_user_can_globally_search_products()
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
        $user = $this->asRegularUser()->withPermissionsTo('view own products')->signIn();
        $user1 = $this->createUser();

        $this->factory()->for($user1, 'creator')->create(['name' => 'PRODUCT KONKORD']);
        $record = $this->factory()->for($user, 'creator')->create(['name' => 'PRODUCT INOKLAPS']);

        $this->getJson('/api/search?q=PRODUCT')
            ->assertJsonCount(1, '0.data')
            ->assertJsonPath('0.data.0.id', $record->id)
            ->assertJsonPath('0.data.0.path', $record->path)
            ->assertJsonPath('0.data.0.display_name', $record->display_name);
    }

    public function test_user_can_force_delete_resource_record()
    {
        $this->signIn();

        $record = tap($this->factory()->create())->delete();

        $this->deleteJson($this->forceDeleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 0);
    }

    public function test_user_can_soft_delete_resource_record()
    {
        $this->signIn();

        $record = $this->factory()->create();

        $this->deleteJson($this->deleteEndpoint($record))->assertNoContent();
        $this->assertDatabaseCount($this->tableName(), 1);
    }

    public function test_user_can_export_products()
    {
        $this->performExportTest();
    }

    public function test_user_can_create_resource_record_with_custom_fields()
    {
        $this->signIn();

        $response = $this->postJson($this->createEndpoint(), array_merge([
            'name' => 'Macbook Pro',
            'unit_price' => 1500,
            'tax_label' => 'DDV',
            'tax_rate' => 18,
        ], $this->customFieldsPayload()))->assertCreated();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_update_resource_record_with_custom_fields()
    {
        $this->signIn();
        $record = $this->factory()->create();

        $response = $this->putJson($this->updateEndpoint($record), array_merge([
            'name' => 'Macbook Pro',
            'unit_price' => 1500,
            'tax_label' => 'DDV',
            'tax_rate' => 18,
        ], $this->customFieldsPayload()))->assertOk();

        $this->assertThatResponseHasCustomFieldsValues($response);
    }

    public function test_user_can_import_products()
    {
        $this->createUser();

        $this->performImportTest();
    }

    public function test_user_can_import_products_with_custom_fields()
    {
        $this->createUser();

        $this->performImportWithCustomFieldsTest();
    }

    public function test_it_properly_finds_duplicate_products_during_import_via_name()
    {
        $this->createUser();
        $this->factory()->create(['name' => 'Duplicate Name']);

        $this->performImportWithDuplicateTest(['name' => 'Duplicate Name']);
    }

    public function test_it_properly_finds_duplicate_products_during_import_via_sku()
    {
        $this->createUser();
        $this->factory()->create(['sku' => '001']);

        $this->performImportWithDuplicateTest(['sku' => '001']);
    }

    public function test_user_can_load_the_products_table()
    {
        $this->performTestTableLoad();
    }

    public function test_products_table_loads_all_fields()
    {
        $this->performTestTableCanLoadWithAllFields();
    }

    public function test_products_table_can_be_customized()
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

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'created_at', 'created_by', 'description', 'direct_cost', 'display_name', 'id', 'is_active', 'name', 'sku', 'tax_label', 'tax_rate', 'unit', 'unit_price', 'updated_at', 'path', 'was_recently_created', 'authorizations' => [
                'create', 'delete', 'update', 'view', 'viewAny',
            ],
        ]);
    }
}
