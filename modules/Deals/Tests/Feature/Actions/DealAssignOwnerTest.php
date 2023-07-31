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
use Modules\Users\Actions\AssignOwnerAction;

class DealAssignOwnerTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'deals';

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new AssignOwnerAction;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_deal_assign_owner_action()
    {
        $this->signIn();
        $user = $this->createUser();
        $deal = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $user->id,
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertEquals($user->id, $deal->fresh()->user_id);
    }

    public function test_authorized_user_can_run_deal_assign_owner_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all deals')->signIn();

        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $user->id,
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertEquals($user->id, $deal->fresh()->user_id);
    }

    public function test_unauthorized_user_can_run_deal_assign_owner_action_on_own_deal()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own deals')->signIn();
        $user = $this->createUser();

        $dealForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherDeal = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $user->id,
            'ids' => [$otherDeal->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $user->id,
            'ids' => [$dealForSignedIn->id],
        ]);

        $this->assertEquals($user->id, $dealForSignedIn->fresh()->user_id);
    }

    public function test_deal_assign_owner_action_requires_owner()
    {
        $this->signIn();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [],
        ])->assertJsonValidationErrors(['user_id']);
    }
}
