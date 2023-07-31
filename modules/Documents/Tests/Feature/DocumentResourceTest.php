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

namespace Modules\Documents\Tests\Feature;

use Modules\Brands\Models\Brand;
use Modules\Contacts\Models\Company;
use Modules\Contacts\Models\Contact;
use Modules\Core\Models\ModelVisibilityGroup;
use Modules\Core\Tests\ResourceTestCase;
use Modules\Deals\Models\Deal;
use Modules\Documents\Enums\DocumentViewType;
use Modules\Documents\Models\DocumentSigner;
use Modules\Documents\Models\DocumentType;
use Modules\Users\Models\User;

class DocumentResourceTest extends ResourceTestCase
{
    protected $resourceName = 'documents';

    public function test_user_can_create_resource_record()
    {
        $this->signIn();

        $user = $this->createUser();
        $brand = Brand::factory()->create();
        $contact = Contact::factory()->create();
        $type = DocumentType::factory()->create();
        $company = Company::factory()->create();
        $deal = Deal::factory()->create();

        $response = $this->postJson($this->createEndpoint(), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'requires_signature' => true,
            'signers' => [
                ['name' => 'John Doe', 'email' => 'john@example.com', 'send_email' => true],
            ],
            'recipients' => [
                ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
            'deals' => [$deal->id],
            'contacts' => [$contact->id],
            'companies' => [$company->id],
        ])
            ->assertCreated();

        $this->assertResourceJsonStructure($response);

        $response->assertJsonCount(1, 'companies')
            ->assertJsonCount(1, 'deals')
            ->assertJsonCount(1, 'contacts')
            ->assertJsonCount(1, 'signers')
            ->assertJsonCount(1, 'recipients')
            ->assertJson([
                'companies' => [['id' => $company->id]],
                'deals' => [['id' => $deal->id]],
                'contacts' => [['id' => $contact->id]],
                'signers' => [['name' => 'John Doe', 'email' => 'john@example.com', 'send_email' => true]],
                'recipients' => [['name' => 'Jane Doe', 'email' => 'jane@example.com', 'send_email' => true]],

                'title' => 'Proposal Document',

                'document_type_id' => $type->id,

                'brand_id' => $brand->id,

                'user_id' => $user->id,

                'was_recently_created' => true,
                'path' => '/documents/1',
                'display_name' => 'Proposal Document',
            ]);
    }

    public function test_user_cant_create_document_with_restricted_visibility_brand()
    {
        $this->asRegularUser()->signIn();

        $brand = $this->newBrandFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->postJson(
            $this->createEndpoint(),
            ['brand_id' => $brand->id]
        )
            ->assertJsonValidationErrors(['brand_id' => 'This brand id value is forbidden.']);
    }

    public function test_user_cant_update_document_with_restricted_visibility_brand()
    {
        $this->asRegularUser()->signIn();
        $document = $this->factory()->create();
        $brand = $this->newBrandFactoryWithVisibilityGroup('users', User::factory())->create();

        $this->putJson(
            $this->updateEndpoint($document),
            ['brand_id' => $brand->id]
        )
            ->assertJsonValidationErrors(['brand_id' => 'This brand id value is forbidden.']);
    }

    public function test_it_updates_only_signer_send_email_attribute_when_document_is_accepted()
    {
        $user = $this->signIn();
        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->singable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false]), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'signers' => [
                ['name' => 'Changed Name', 'email' => 'john@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
        ])->assertOk();

        $signer = $document->signers->first();

        $this->assertTrue($signer->send_email);
        $this->assertNotSame('Changed Name', $signer->name);
    }

    public function test_it_doesnt_add_new_signers_when_document_is_accepted()
    {
        $user = $this->signIn();
        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->singable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false])->signed(), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'signers' => [
                ['name' => 'Changed Name', 'email' => 'john@example.com', 'send_email' => true],
                ['name' => 'New Name', 'email' => 'new@example.com', 'send_email' => true],
            ],
            'user_id' => $user->id,
        ])->assertOk();

        $this->assertSame(1, $document->signers()->count());
    }

    public function test_it_does_not_delete_all_signers_when_requires_signature_attribute_is_not_provided()
    {
        $user = $this->signIn();
        $brand = Brand::factory()->create();
        $type = DocumentType::factory()->create();

        $document = $this->factory()->accepted()
            ->singable()
            ->has(DocumentSigner::factory(['email' => 'john@example.com', 'send_email' => false])->signed(), 'signers')
            ->create();

        $this->putJson($this->updateEndpoint($document), [
            'title' => 'Proposal Document',
            'brand_id' => $brand->id,
            'document_type_id' => $type->id,
            'view_type' => DocumentViewType::NAV_LEFT->value,
            'user_id' => $user->id,
        ])->assertOk();

        $this->assertSame(1, $document->signers()->count());
    }

    protected function newBrandFactoryWithVisibilityGroup($group, $attached)
    {
        return Brand::factory()->has(
            ModelVisibilityGroup::factory()->{$group}()->hasAttached($attached),
            'visibilityGroup'
        );
    }

    protected function assertResourceJsonStructure($response)
    {
        $response->assertJsonStructure([
            'actions', 'accepted_at', 'amount', 'associations', 'authorizations', 'billable', 'brand_id', 'changelog', 'companies', 'contacts', 'deals', 'content', 'created_at', 'created_by', 'display_name', 'document_type_id', 'google_fonts', 'id', 'last_date_sent', 'marked_accepted_by', 'original_date_sent', 'owner_assigned_date', 'path', 'public_url', 'recipients', 'requires_signature', 'send_at', 'send_mail_account_id', 'send_mail_body', 'send_mail_subject', 'signers', 'status', 'timeline_component', 'timeline_relation', 'title', 'type', 'updated_at', 'user', 'user_id', 'view_type', 'was_recently_created',
        ]);
    }
}
