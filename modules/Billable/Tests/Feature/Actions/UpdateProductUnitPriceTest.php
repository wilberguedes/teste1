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

use Modules\Billable\Actions\UpdateProductUnitPrice;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Tests\ResourceTestCase;

class UpdateProductUnitPriceTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'products';

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new UpdateProductUnitPrice;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_update_unit_price_action()
    {
        $this->signIn();
        $product = $this->factory()->create(['unit_price' => 1000]);

        $this->postJson($this->actionEndpoint($this->action), [
            'unit_price' => 2000,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(2000, $product->fresh()->unit_price);
    }

    public function test_authorized_user_can_run_update_unit_price_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();

        $user = $this->createUser();
        $product = $this->factory()->for($user, 'creator')->create(['unit_price' => 1000]);

        $this->postJson($this->actionEndpoint($this->action), [
            'unit_price' => 2000,
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertEquals(2000, $product->fresh()->unit_price);
    }

    public function test_unauthorized_user_can_run_update_unit_price_action_on_own_product()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();
        $this->createUser();

        $productForSignedIn = $this->factory()->for($signedInUser, 'creator')->create(['unit_price' => 1000]);
        $otherProduct = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'unit_price' => 2000,
            'ids' => [$otherProduct->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'unit_price' => 2000,
            'ids' => [$productForSignedIn->id],
        ]);
        $this->assertEquals(2000, $productForSignedIn->fresh()->unit_price);
    }

    public function test_update_unit_price_action_requires_price()
    {
        $this->signIn();
        $this->createUser();
        $product = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'unit_price' => '',
            'ids' => [$product->id],
        ])->assertJsonValidationErrors('unit_price');
    }
}
