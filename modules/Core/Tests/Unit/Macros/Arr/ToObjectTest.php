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

namespace Modules\Core\Tests\Unit\Macros\Arr;

use Illuminate\Support\Arr;
use Tests\TestCase;

class ToObjectTest extends TestCase
{
    public function test_it_converts_array_to_object()
    {
        $object = Arr::toObject(['key' => 'value', 'children' => ['key' => 'value']]);

        $this->assertIsObject($object);
        $this->assertTrue(property_exists($object, 'key'));
        $this->assertSame('value', $object->key);
        $this->assertTrue(property_exists($object, 'children'));
        $this->assertSame('value', $object->children->key);
    }

    public function test_it_returns_empty_object_when_the_provided_value_is_not_an_array()
    {
        $object = Arr::toObject(null);

        $this->assertIsObject($object);
    }
}
