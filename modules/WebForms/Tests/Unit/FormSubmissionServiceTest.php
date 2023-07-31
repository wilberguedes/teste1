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

namespace Modules\WebForms\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Contacts\Enums\PhoneType;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Phone;
use Modules\Contacts\Models\Source;
use Modules\Core\Database\Seeders\CountriesSeeder;
use Modules\Core\Database\Seeders\SettingsSeeder;
use Modules\Core\Fields\User;
use Modules\Deals\Models\Deal;
use Modules\WebForms\Http\Requests\WebFormRequest;
use Modules\WebForms\Mail\WebFormSubmitted;
use Modules\WebForms\Models\WebForm;
use Modules\WebForms\Services\FormSubmissionService;
use Tests\TestCase;

class FormSubmissionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        User::setAssigneer(null);

        parent::tearDown();
    }

    public function test_web_form_can_be_submitted()
    {
        $this->createWebFormSource();
        $this->seed(SettingsSeeder::class);

        Storage::fake();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->addFieldSection('last_name', 'contacts', ['requestAttribute' => 'contact_last_name'])
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'contact_phone'])
            ->addFileSection('contacts', ['requestAttribute' => 'contacts_file'])

            ->addFieldSection('name', 'deals', ['requestAttribute' => 'deal_name'])
            ->addFieldSection('amount', 'deals', ['requestAttribute' => 'deal_amount'])
            ->addFieldSection('expected_close_date', 'deals', ['requestAttribute' => 'deal_expected_close_date'])
            ->addFileSection('deals', ['requestAttribute' => 'deals_file'])

            ->addFieldSection('name', 'companies', ['requestAttribute' => 'company_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->addFileSection('companies', ['requestAttribute' => 'companies_file'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'contact_email' => 'john@example.com',
            'contact_phone' => [['number' => '+1547-7745-55', 'type' => 'work']],
            'contacts_file' => UploadedFile::fake()->image('contacts_photo.jpg'),

            'deal_name' => 'Deal',
            'deal_amount' => 1250,
            'deal_expected_close_date' => '2021-02-12 12:00:00',
            'deals_file' => UploadedFile::fake()->image('deals_photo.jpg'),

            'company_name' => 'KONKORD DIGITAL',
            'company_email' => 'konkord@example.com',
            'company_domain' => 'concordcrm.com',
            'companies_file' => UploadedFile::fake()->image('companies_photo.jpg'),
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('deals', [
            'name' => 'Deal',
            'amount' => 1250,
            'web_form_id' => $form->id,
            'expected_close_date' => '2021-02-12 12:00:00',
            'user_id' => $form->user_id,
            'pipeline_id' => $form->submit_data['pipeline_id'],
            'stage_id' => $form->submit_data['stage_id'],
        ]);

        $deal = Deal::first();
        $this->assertEquals('deals_photo', $deal->media->first()->filename);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'user_id' => $form->user_id,
            'source_id' => Source::findByFlag('web-form')->id,
        ]);

        $contact = Contact::first();
        $this->assertEquals('contacts_photo', $contact->media->first()->filename);

        $this->assertDatabaseHas('phones', [
            'type' => PhoneType::work,
            'number' => '+1547-7745-55',
            'phoneable_id' => $contact->id,
            'phoneable_type' => Contact::class,
        ]);

        $company = Company::first();
        $this->assertEquals('companies_photo', $company->media->first()->filename);

        $this->assertDatabaseHas('companies', [
            'name' => 'KONKORD DIGITAL',
            'email' => 'konkord@example.com',
            'domain' => 'concordcrm.com',
            'source_id' => Source::findByFlag('web-form')->id,
        ]);
    }

    public function test_it_send_notifications_when_form_is_submitted()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create(['notifications' => ['john@example.com', 'doe@example.com']]);

        $request = $this->prepareRequestForSubmission($form, [
            'email' => 'email@example.com',
        ]);

        Mail::fake();

        (new FormSubmissionService())->process($request);

        Mail::assertQueued(function (WebFormSubmitted $mail) {
            return $mail->hasTo('john@example.com');
        });

        Mail::assertQueued(function (WebFormSubmitted $mail) {
            return $mail->hasTo('doe@example.com');
        });

        Mail::assertQueued(WebFormSubmitted::class, 2);
    }

    public function test_it_does_not_send_notifications_when_no_emails_provided()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create(['notifications' => []]);

        $request = $this->prepareRequestForSubmission($form, [
            'email' => 'email@example.com',
        ]);

        Mail::fake();

        (new FormSubmissionService())->process($request);

        Mail::assertNothingSent();
    }

    public function test_it_does_not_create_company_when_the_web_form_doesnt_have_company_fields()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'email' => 'email@example.com',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_it_uses_contact_phone_number_as_first_name_when_doesnt_have_first_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'contact_phone'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'contact_phone' => [['number' => '+1547-7745-55', 'type' => 'work']],
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '+1547-7745-55',
        ]);
    }

    public function test_it_uses_contact_first_name_value_when_there_is_no_deal_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'contact_first_name' => 'John',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('deals', [
            'name' => 'John Deal',
        ]);
    }

    public function test_it_uses_contact_first_name_value_when_there_is_no_company_name_field()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'contact_first_name'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'contact_first_name' => 'John',
            'company_domain' => 'concordcrm.com',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('companies', [
            'name' => 'John Company',
        ]);
    }

    public function test_deal_prefix_is_added()
    {
        $this->createWebFormSource();

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'contact_email'])
            ->addFieldSection('name', 'deals', ['requestAttribute' => 'deal_name'])
            ->create(['title_prefix' => 'PREFIX-']);

        $request = $this->prepareRequestForSubmission($form, [
            'contact_email' => 'john@example.com',
            'deal_name' => 'Deal Name',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('deals', [
            'name' => 'PREFIX-Deal Name',
        ]);
    }

    public function test_it_updates_the_contact_if_exists_by_email()
    {
        $this->createWebFormSource();

        Contact::factory()->create(['first_name' => 'John', 'email' => 'john@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'email' => 'john@example.com',
            'first_name' => 'Updated First Name',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('contacts', ['first_name' => 'Updated First Name']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_it_updates_the_contact_if_exists_by_phone()
    {
        $this->createWebFormSource();
        $this->seed(CountriesSeeder::class);

        $contact = Contact::factory()->has(Phone::factory())->create(['first_name' => 'John']);
        $number = $contact->phones->first()->number;

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('phones', 'contacts', ['requestAttribute' => 'phones'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'first_name' => 'Jake',
            'phones' => [['number' => $number, 'type' => 'work']],
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('contacts', ['first_name' => 'Jake']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_it_does_not_update_the_contact_user_id_when_exists()
    {
        $this->createWebFormSource();
        $user = $this->createUser();
        Contact::factory()->for($user)->create(['email' => 'john@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('email', 'contacts', ['requestAttribute' => 'email'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'email' => 'changed@example.com',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('contacts', ['user_id' => $user->id]);
    }

    public function test_it_updates_the_company_if_exists_by_email()
    {
        $this->createWebFormSource();

        Company::factory()->create(['email' => 'konkord@example.com', 'domain' => 'old.com']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->addFieldSection('domain', 'companies', ['requestAttribute' => 'company_domain'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'first_name' => 'John',
            'company_email' => 'konkord@example.com',
            'company_domain' => 'new.com',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('companies', ['domain' => 'new.com']);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_it_does_not_update_the_company_user_id_when_exists()
    {
        $this->createWebFormSource();
        $user = $this->createUser();
        Company::factory()->for($user)->create(['email' => 'konkord@example.com']);

        $form = WebForm::factory()
            ->addFieldSection('first_name', 'contacts', ['requestAttribute' => 'first_name'])
            ->addFieldSection('email', 'companies', ['requestAttribute' => 'company_email'])
            ->create();

        $request = $this->prepareRequestForSubmission($form, [
            'first_name' => 'John',
            'company_email' => 'konkord@example.com',
        ]);

        (new FormSubmissionService())->process($request);

        $this->assertDatabaseHas('companies', ['user_id' => $user->id]);
    }

    protected function createWebFormSource()
    {
        Source::factory()->create(['name' => 'Web Form', 'flag' => 'web-form']);
    }

    protected function prepareRequestForSubmission($form, $attributes = [])
    {
        $request = new WebFormRequest;

        return $request->setRouteResolver(function () use ($form, $request) {
            $route = new Route('POST', '/forms/f/{uuid}', []);
            $route->bind($request);
            $route->setParameter('uuid', $form->uuid);

            return $route;
        })->merge($attributes)
            ->setOriginalInput();
    }
}
