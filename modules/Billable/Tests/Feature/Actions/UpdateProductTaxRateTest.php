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

namespace Modules\Billable\Tests\Feature\Actions;

use Modules\Billable\Actions\UpdateProductTaxRate;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Tests\ResourceTestCase;

class UpdateProductTaxRateTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'products';

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new UpdateProductTaxRate;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_update_tax_rate_action()
    {
        $this->signIn();
        $product = $this->factory()->create(['tax_rate' => 12]);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => 15,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(15, $product->fresh()->tax_rate);
    }

    public function test_authorized_user_can_run_update_tax_rate_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();

        $user = $this->createUser();
        $product = $this->factory()->for($user, 'creator')->create(['tax_rate' => 12]);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => 15,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(15, $product->fresh()->tax_rate);
    }

    public function test_unauthorized_user_can_run_update_tax_rate_action_on_own_product()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();
        $this->createUser();

        $productForSignedIn = $this->factory()->for($signedInUser, 'creator')->create(['tax_rate' => 12]);
        $otherProduct = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => 15,
            'ids' => [$otherProduct->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => 15,
            'ids' => [$productForSignedIn->id],
        ]);
        $this->assertEquals(15, $productForSignedIn->fresh()->tax_rate);
    }

    public function test_it_sets_the_tax_rate_to_zero_or_when_the_provided_value_is_null_or_empty()
    {
        $this->signIn();
        $product = $this->factory()->create(['tax_rate' => 5]);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => '',
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(0, $product->fresh()->tax_rate);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => null,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(0, $product->fresh()->tax_rate);
    }

    public function test_tax_rate_action_accepts_float()
    {
        $this->signIn();
        $product = $this->factory()->create(['tax_rate' => 12.55]);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => 12.55,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(12.55, $product->fresh()->tax_rate);
    }

    public function test_tax_rate_action_accepts_numeric()
    {
        $this->signIn();
        $product = $this->factory()->create(['tax_rate' => '12.55']);

        $this->postJson($this->actionEndpoint($this->action), [
            'tax_rate' => '12.55',
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals('12.550', $product->fresh()->tax_rate);
    }
}
