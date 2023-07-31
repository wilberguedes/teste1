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

namespace Modules\Core\Tests\Feature\Filters;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Modules\Core\Filters\Checkbox;
use Modules\Core\Tests\Concerns\TestsFilters;
use Tests\Fixtures\Event;
use Tests\TestCase;

class CheckboxFilterTest extends TestCase
{
    use TestsFilters;

    protected static $filter = Checkbox::class;

    public function test_checkbox_filter_rule_with_in_operator()
    {
        Event::factory()->count(3)->state(new Sequence(
            ['total_guests' => 1],
            ['total_guests' => 2],
            ['total_guests' => 3]
        ))->create();

        $result = $this->perform('total_guests', 'in', [2, 3]);

        $this->assertEquals($result[0]->total_guests, 2);
        $this->assertEquals($result[1]->total_guests, 3);
        $this->assertCount(2, $result);
    }
}
