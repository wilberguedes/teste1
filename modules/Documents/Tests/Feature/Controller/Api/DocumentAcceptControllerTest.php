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

namespace Modules\Documents\Tests\Feature\Controller\Api;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Documents\Enums\DocumentStatus;
use Modules\Documents\Mail\DocumentSignedThankYouMessage;
use Modules\Documents\Models\Document;
use Modules\Documents\Models\DocumentSigner;
use Modules\Documents\Notifications\DocumentAccepted;
use Modules\Documents\Notifications\SignerSignedDocument;
use Tests\TestCase;

class DocumentAcceptControllerTest extends TestCase
{
    public function test_document_can_be_accepted()
    {
        Notification::fake();

        $document = Document::factory()->draft()->create();

        $this->postJson("/api/d/$document->uuid/accept")->assertNoContent();

        $document->refresh();

        $this->assertEquals(DocumentStatus::ACCEPTED, $document->status);
        $this->assertNotNull($document->accepted_at);

        Notification::assertSentTimes(DocumentAccepted::class, 1);
    }

    public function test_cannot_accept_already_accepted_document()
    {
        $document = Document::factory()->accepted()->create();

        $this->postJson("/api/d/$document->uuid/accept")->assertNotFound();
    }

    public function test_document_can_be_signed()
    {
        Notification::fake();
        Mail::fake();

        $document = Document::factory()
            ->singable()
            ->draft()->has(DocumentSigner::factory(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => $email = $document->signers[0]->email,
            'signature' => $document->signers[0]['name'],
        ])->assertNoContent();

        $document->refresh();

        $this->assertEquals(DocumentStatus::ACCEPTED, $document->status);
        Notification::assertSentTimes(SignerSignedDocument::class, 1);
        Mail::assertSent(DocumentSignedThankYouMessage::class, function (DocumentSignedThankYouMessage $mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_cannot_sign_already_signed_document()
    {
        $document = Document::factory()
            ->singable()
            ->accepted()->has(DocumentSigner::factory()->signed(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => $document->signers[0]->email,
            'signature' => $document->signers[0]['name'],
        ])->assertNotFound();
    }

    public function test_sign_requires_valid_email_address()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'email' => 'invalid-email',
        ])->assertJsonValidationErrorFor('email');
    }

    public function test_sign_requires_signature()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/sign", [
            'signature' => '',
        ])->assertJsonValidationErrorFor('signature');
    }

    public function test_it_can_confirm_the_signer_email_address()
    {
        $document = Document::factory()
            ->singable()
            ->draft()->has(DocumentSigner::factory()->signed(), 'signers')
            ->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => $document->signers[0]->email,
        ])
            ->assertOk()
            ->assertJson(['name' => $document->signers[0]->name]);
    }

    public function test_it_does_not_confirm_signer_when_no_email_provided()
    {
        $document = Document::factory()->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => '',
        ])->assertNoContent();
    }

    public function test_it_does_not_confirm_signer_when_no_signer_not_exists()
    {
        $document = Document::factory()->has(DocumentSigner::factory(), 'signers')->create();

        $this->postJson("/api/d/$document->uuid/validate", [
            'email' => 'unknown@example.com',
        ])->assertNoContent();
    }

    public function test_it_fails_when_document_is_not_found_by_the_provided_uuid()
    {
        $this->postJson('/api/d/unknown/sign')->assertNotFound();
        $this->postJson('/api/d/unknown/accept')->assertNotFound();
        $this->postJson('/api/d/unknown/validate', ['email' => 'email@example.com'])->assertNotFound();
    }
}
