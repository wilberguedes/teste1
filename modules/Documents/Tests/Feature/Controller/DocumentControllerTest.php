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

namespace Modules\Documents\Tests\Feature\Controller;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Modules\Documents\Models\Document;
use Modules\Documents\Notifications\DocumentViewed;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    public function test_document_can_be_publicly_viewed()
    {
        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/")
            ->assertOk()
            ->assertSee($document->type->name);
    }

    public function test_lost_document_cannot_be_publicly_viewed()
    {
        $document = Document::factory()->lost()->create();

        $this->get("/d/$document->uuid/")->assertNotFound();
    }

    public function test_authenticated_user_can_publicly_view_lost_document()
    {
        $this->signIn();

        $document = Document::factory()->lost()->create();

        $this->get("/d/$document->uuid/")->assertOk();
    }

    public function test_it_records_documents_views()
    {
        Notification::fake();
        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/");

        $this->assertDatabaseHas('views', ['viewable_type' => Document::class, 'viewable_id' => $document->id]);
        $this->assertDatabaseHas('changelog', ['subject_type' => Document::class, 'subject_id' => $document->id]);
        Notification::assertSentTimes(DocumentViewed::class, 1);
        Notification::assertSentTo($document->user, DocumentViewed::class);
    }

    public function test_it_records_views_hourly()
    {
        Carbon::setTestNow(now()->subMinute(1));
        Notification::fake();
        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/");
        $this->get("/d/$document->uuid/");

        $this->assertDatabaseCount('views', 1);
        Notification::assertSentTimes(DocumentViewed::class, 1);
        Carbon::setTestNow(Carbon::now()->addHour(1));

        $this->get("/d/$document->uuid/");

        $this->assertDatabaseCount('views', 2);
        Notification::assertSentTimes(DocumentViewed::class, 2);
    }

    public function test_it_does_not_record_views_when_user_is_authenticated()
    {
        Notification::fake();

        $this->signIn();

        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/");

        $this->assertDatabaseEmpty('views');
        Notification::assertSentTimes(DocumentViewed::class, 0);
    }

    public function test_document_pdf_can_be_publicly_viewed()
    {
        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="'.$document->pdfFilename().'"');
    }

    public function test_document_pdf_can_be_publicly_downloaded()
    {
        $document = Document::factory()->sent()->create();

        $this->get("/d/$document->uuid/pdf?output=download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="'.$document->pdfFilename().'"');
    }

    public function test_it_does_not_throw_error_on_pdf_when_document_content_is_empty()
    {
        $document = Document::factory()->sent()->create(['content' => null]);

        $this->get("/d/$document->uuid/pdf")->assertOk();
    }

    public function test_lost_document_pdf_cannot_be_publicly_downloaded()
    {
        $document = Document::factory()->lost()->create();

        $this->get("/d/$document->uuid/pdf")->assertNotFound();
        $this->get("/d/$document->uuid/pdf?output=download")->assertNotFound();
    }

    public function test_authenticated_user_can_publicly_download_lost_document_pdf()
    {
        $this->signIn();

        $document = Document::factory()->lost()->create();

        $this->get("/d/$document->uuid/pdf")->assertOk();
        $this->get("/d/$document->uuid/pdf?output=download")->assertOk();
    }

    public function test_it_does_logs_activity_when_pdf_is_viewed()
    {
        Carbon::setTestNow(now()->subMinute(1));
        $document = Document::factory()->sent()->create();

        Carbon::setTestNow(null);
        $this->get("/d/$document->uuid/pdf")->assertOk();

        $this->assertEquals(
            'documents::document.activity.downloaded',
            $document->changelog()->latest()->first()->properties['lang']['key']
        );
    }

    public function test_it_does_not_logs_when_authenticated_users_views_pdf()
    {
        $this->signIn();

        Carbon::setTestNow(now()->subMinute(1));
        $document = Document::factory()->sent()->create();

        Carbon::setTestNow(null);
        $this->get("/d/$document->uuid/pdf")->assertOk();

        $this->assertNotEquals(
            'documents::document.activity.downloaded',
            $document->changelog()->latest()->first()->properties['lang']['key']
        );
    }
}
