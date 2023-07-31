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

use Modules\Billable\Models\Billable;
use Modules\Billable\Models\BillableProduct;
use Modules\Deals\Models\Deal;
use Tests\TestCase;

class BillableControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_the_billable_endpoints()
    {
        $this->getJson('FAKE_RESOURCE_NAME/FAKE_RESOURCE_ID/billable')->assertUnauthorized();
    }

    public function test_billable_can_be_created()
    {
        $this->signIn();

        $billable = Deal::factory()->create();

        $this->postJson("/api/deals/{$billable->getKey()}/billable", [
            'tax_type' => 'exclusive',
            'products' => [
                $this->sampleProduct(),
            ],
        ])->assertJson(['tax_type' => 'exclusive'])
            ->assertJsonPath('products.0.name', 'Product Name')
            ->assertJsonPath('products.0.description', 'Product Description')
            ->assertJsonPath('products.0.unit_price', 1000)
            ->assertJsonPath('products.0.qty', '2.00')
            ->assertJsonPath('products.0.tax_label', 'TAX')
            ->assertJsonPath('products.0.tax_rate', 10)
            ->assertJsonPath('products.0.unit', 'kg')
            ->assertJsonPath('products.0.display_order', 1)
            ->assertJsonPath('products.0.discount_total', '10.00')
            ->assertJsonPath('products.0.discount_type', 'percent');

        $this->assertDatabaseHas('billables', [
            'billableable_type' => $billable::class,
            'billableable_id' => $billable->getKey(),
        ]);
    }

    public function test_billable_product_can_be_updated()
    {
        $this->signIn();

        $billable = Deal::factory()->create();

        $billable = Billable::factory()->withBillableable()
            ->taxExclusive()
            ->withBillableable(Deal::factory())
            ->has(BillableProduct::factory(), 'products')
            ->create();

        $this->postJson("/api/deals/{$billable->billableable->getKey()}/billable", [
            'products' => [
                array_merge($billable->products[0]->toArray(), [
                    'name' => 'Changed Product Name',
                ]),
            ],
        ])->assertJsonPath('products.0.name', 'Changed Product Name');
    }

    protected function sampleProduct($attributes = [])
    {
        return array_merge([
            'name' => 'Product Name',
            'description' => 'Product Description',
            'unit_price' => 1000,
            'qty' => 2,
            'tax_label' => 'TAX',
            'tax_rate' => 10,
            'unit' => 'kg',
            'display_order' => 1,
            'discount_total' => 10,
            'discount_type' => 'percent',
        ], $attributes);
    }
}
