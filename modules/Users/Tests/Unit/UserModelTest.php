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

namespace Modules\Users\Tests\Unit;

use Modules\Activities\Models\Activity;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Deals\Models\Deal;
use Modules\MailClient\Models\PredefinedMailTemplate;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_has_companies()
    {
        $user = User::factory()->has(Company::factory()->count(2))->create();

        $this->assertCount(2, $user->companies);
    }

    public function test_user_has_contacts()
    {
        $user = User::factory()->has(Contact::factory()->count(2))->create();

        $this->assertCount(2, $user->contacts);
    }

    public function test_user_has_deals()
    {
        $user = User::factory()->has(Deal::factory()->count(2))->create();

        $this->assertCount(2, $user->deals);
    }

    public function test_user_has_activities()
    {
        $user = User::factory()->has(Activity::factory()->count(2))->create();

        $this->assertCount(2, $user->activities);
    }

    public function test_user_has_predefined_mail_templates()
    {
        $user = User::factory()->has(PredefinedMailTemplate::factory()->count(2))->create();

        $this->assertCount(2, $user->predefinedMailTemplates);
    }
}
