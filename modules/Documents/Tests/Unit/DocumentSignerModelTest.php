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
use Modules\Documents\Models\DocumentSigner;
use Tests\TestCase;

class DocumentSignerModelTest extends TestCase
{
    public function test_signer_has_document()
    {
        $signer = DocumentSigner::factory()->make();

        $this->assertInstanceOf(BelongsTo::class, $signer->document());
    }

    public function test_it_can_determine_if_signer_has_signature()
    {
        $signer = DocumentSigner::factory()->signed()->make();

        $this->assertTrue($signer->hasSignature());
        $this->assertFalse($signer->missingSignature());
    }

    public function test_it_can_determine_if_signer_missing_signature()
    {
        $signer = DocumentSigner::factory()->make();

        $this->assertTrue($signer->missingSignature());
        $this->assertFalse($signer->hasSignature());
    }
}
