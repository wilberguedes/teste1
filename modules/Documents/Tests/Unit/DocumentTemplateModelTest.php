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

namespace Modules\Documents\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Modules\Documents\Enums\DocumentViewType;
use Modules\Documents\Models\DocumentTemplate;
use Modules\Users\Models\User;
use Tests\TestCase;

class DocumentTemplateModelTest extends TestCase
{
    public function test_document_template_has_user()
    {
        $template = DocumentTemplate::factory()->for(User::factory())->create();

        $this->assertInstanceOf(BelongsTo::class, $template->user());
        $this->assertInstanceOf(User::class, $template->user);
    }

    public function test_document_template_view_type_is_casted()
    {
        $template = DocumentTemplate::factory()->create(['view_type' => DocumentViewType::NAV_LEFT]);

        $this->assertInstanceOf(DocumentViewType::class, $template->view_type);
        $this->assertEquals(DocumentViewType::NAV_LEFT, $template->view_type);
    }

    public function test_document_template_has_used_google_fonts()
    {
        $template = DocumentTemplate::factory()->make();

        $this->assertTrue(method_exists($template, 'usedGoogleFonts'));
        $this->assertInstanceOf(Collection::class, $template->usedGoogleFonts());
    }
}
