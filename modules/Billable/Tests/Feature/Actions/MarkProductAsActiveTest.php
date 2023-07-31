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

use Modules\Billable\Actions\MarkProductAsActive;
use Modules\Core\Database\Seeders\PermissionsSeeder;
use Modules\Core\Tests\ResourceTestCase;

class MarkProductAsActiveTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'products';

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new MarkProductAsActive;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_mark_as_active_action()
    {
        $this->signIn();
        $product = $this->factory()->inactive()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_authorized_user_can_run_mark_as_active_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all products')->signIn();

        $user = $this->createUser();
        $product = $this->factory()->inactive()->for($user, 'creator')->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$product->id],
        ])->assertOk();

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_unauthorized_user_can_run_mark_as_active_action_on_own_deal()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own products')->signIn();
        $this->createUser();

        $productForSignedIn = $this->factory()->inactive()->for($signedInUser, 'creator')->create();
        $otherProduct = $this->factory()->inactive()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$otherProduct->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$productForSignedIn->id],
        ]);

        $this->assertTrue($productForSignedIn->fresh()->is_active);
    }
}
