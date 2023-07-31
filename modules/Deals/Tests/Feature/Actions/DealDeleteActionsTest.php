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

class DealDeleteActionsTest extends ResourceTestCase
{
    protected $resourceName = 'deals';

    public function test_super_admin_user_can_run_deal_delete_action()
    {
        $this->signIn();
        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();
        $action = $this->findAction('delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    }

    public function test_authorized_user_can_run_deal_delete_action()
    {
        $this->seed(PermissionsSeeder::class);

        $this->asRegularUser()->withPermissionsTo('delete any deal')->signIn();

        $this->createUser();
        $deal = $this->factory()->create();
        $action = $this->findAction('delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    }

    public function test_authorized_user_can_run_deal_delete_action_only_on_own_deals()
    {
        $this->seed(PermissionsSeeder::class);

        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own deals')->signIn();
        $this->createUser();

        $dealForSignedIn = $this->factory()->create(['user_id' => $signedInUser->id]);
        $otherActivity = $this->factory()->create();

        $action = $this->findAction('delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$otherActivity->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->assertDatabaseHas('deals', ['id' => $otherActivity->id]);

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$dealForSignedIn->id],
        ]);

        $this->assertSoftDeleted('deals', ['id' => $dealForSignedIn->id]);
    }

    public function test_unauthorized_user_can_run_deal_delete_action_on_own_deal()
    {
        $this->seed(PermissionsSeeder::class);

        $signedInUser = $this->asRegularUser()->withPermissionsTo('delete own deals')->signIn();
        $user = $this->createUser();

        $dealForSignedIn = $this->factory()->create(['user_id' => $signedInUser->id]);
        $otherActivity = $this->factory()->for($user)->create();

        $action = $this->findAction('delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$otherActivity->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->assertDatabaseHas('deals', ['id' => $otherActivity->id]);

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$dealForSignedIn->id],
        ]);

        $this->assertSoftDeleted('deals', ['id' => $dealForSignedIn->id]);
    }

    public function test_super_super_admin_user_can_run_deal_bulk_delete_action()
    {
        $this->signIn();
        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();
        $action = $this->findAction('bulk-delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    }

    public function test_authorized_user_can_run_deal_bulk_delete_action()
    {
        $this->seed(PermissionsSeeder::class);

        $this->asRegularUser()->withPermissionsTo('bulk delete deals')->signIn();

        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();
        $action = $this->findAction('bulk-delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    }

    public function test_unauthorized_user_cant_run_deal_bulk_delete_action()
    {
        $this->asRegularUser()->signIn();
        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();
        $action = $this->findAction('bulk-delete');

        $this->postJson($this->actionEndpoint($action), [
            'ids' => [$deal->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->assertDatabaseHas('deals', ['id' => $deal->id]);
    }
}
