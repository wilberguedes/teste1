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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Documents\Models\DocumentType;
use Tests\TestCase;

class DocumentTypeModelTest extends TestCase
{
    public function test_type_has_document()
    {
        $type = DocumentType::factory()->make();

        $this->assertInstanceOf(HasMany::class, $type->documents());
    }

    public function test_document_type_can_be_primary()
    {
        $type = DocumentType::factory()->primary()->create();

        $this->assertTrue($type->isPrimary());

        $type->flag = null;
        $type->save();

        $this->assertFalse($type->isPrimary());
    }

    public function test_document_type_can_be_default()
    {
        $type = DocumentType::factory()->primary()->create();

        DocumentType::setDefault($type->id);

        $this->assertEquals($type->id, DocumentType::getDefaultType());
    }
}
