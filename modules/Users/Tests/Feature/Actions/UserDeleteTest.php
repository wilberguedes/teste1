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

namespace Modules\Users\Tests\Feature\Actions;

use Modules\Core\Tests\ResourceTestCase;
use Modules\Users\Actions\UserDelete;

class UserDeleteTest extends ResourceTestCase
{
    protected $resourceName = 'users';

    protected $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UserDelete;
    }

    protected function tearDown(): void
    {
        unset($this->action);
        parent::tearDown();
    }

    public function test_super_admin_user_can_run_user_delete_action()
    {
        $this->signIn();

        $users = $this->createUser(2);

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $users[1]->id,
            'ids' => [$users[0]->id],
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $users[0]->id]);
    }

    public function test_non_super_admin_user_cant_run_user_delete_action()
    {
        $this->asRegularUser()->signIn();

        $users = $this->createUser(2);

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $users[1]->id,
            'ids' => [$users[0]->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $users[0]->id]);
    }

    public function test_user_cannot_delete_his_own_account()
    {
        $user = $this->signIn();

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $user->id,
            'ids' => [$user->id],
        ])->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_cannot_transfer_the_data_on_delete_on_the_same_user_which_is_about_to_be_deleted()
    {
        $this->signIn();

        $otherUser = $this->createUser();

        $this->postJson($this->actionEndpoint($this->action), [
            'user_id' => $otherUser->id,
            'ids' => [$otherUser->id],
        ])->assertStatus(409);

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_user_delete_action_requires_user_to_transfer_the_data_to()
    {
        $this->signIn();

        $this->postJson($this->actionEndpoint($this->action), [
            'ids' => [],
        ])->assertJsonValidationErrors(['user_id']);
    }
}
