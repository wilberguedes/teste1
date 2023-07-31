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

namespace Modules\Core\Contracts;

use Illuminate\Database\Query\Expression;

interface DisplaysOnCalendar
{
    /**
     * Get the start date
     */
    public function getCalendarStartDate(): string;

    /**
     * Get the end date
     */
    public function getCalendarEndDate(): string;

    /**
     * Indicates whether the event is all day
     */
    public function isAllDay(): bool;

    /**
     * Get the displayable title for the calendar
     */
    public function getCalendarTitle(): string;

    /**
     * Get the calendar start date column name for query
     */
    public static function getCalendarStartColumnName(): string|Expression;

    /**
     * Get the calendar end date column name for query
     */
    public static function getCalendarEndColumnName(): string|Expression;
}
