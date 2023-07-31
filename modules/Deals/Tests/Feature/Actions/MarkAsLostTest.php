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
use Modules\Deals\Actions\MarkAsLost;
use Modules\Deals\Enums\DealStatus;

class MarkAsLostTest extends ResourceTestCase
{
    protected $action;

    protected $resourceName = 'deals';

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new MarkAsLost;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_deal_mark_as_lost_action()
    {
        $this->signIn();
        $deal = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSame(DealStatus::lost, $deal->fresh()->status);
    }

    public function test_authorized_user_can_run_deal_mark_as_lost_action()
    {
        $this->seed(PermissionsSeeder::class);
        $this->asRegularUser()->withPermissionsTo('edit all deals')->signIn();

        $user = $this->createUser();
        $deal = $this->factory()->for($user)->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$deal->id],
        ])->assertOk();

        $this->assertSame(DealStatus::lost, $deal->fresh()->status);
    }

    public function test_unauthorized_user_can_run_deal_mark_as_lost_action_on_own_deal()
    {
        $this->seed(PermissionsSeeder::class);
        $signedInUser = $this->asRegularUser()->withPermissionsTo('edit own deals')->signIn();
        $this->createUser();

        $dealForSignedIn = $this->factory()->for($signedInUser)->create();
        $otherDeal = $this->factory()->create();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$otherDeal->id],
        ])->assertJson(['error' => __('users::user.not_authorized')]);

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [$dealForSignedIn->id],
        ]);

        $this->assertSame(DealStatus::lost, $dealForSignedIn->fresh()->status);
    }
}
