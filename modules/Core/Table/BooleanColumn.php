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

namespace Modules\Core\Table;

class BooleanColumn extends Column
{
    /**
     * Initialize new BooleanColumn instance.
     */
    public function __construct(...$params)
    {
        parent::__construct(...$params);

        $this->centered();
    }

    /**
     * Checkbox checked value
     */
    public mixed $trueValue = true;

    /**
     * Checkbox unchecked value
     */
    public mixed $falseValue = false;

    /**
     * Data heading component
     */
    public string $component = 'table-data-boolean-column';

    /**
     * Checkbox checked value
     */
    public function trueValue(mixed $val): static
    {
        $this->trueValue = $val;

        return $this;
    }

    /**
     * Checkbox unchecked value
     */
    public function falseValue(mixed $val): static
    {
        $this->falseValue = $val;

        return $this;
    }

    /**
     * Additional column meta
     */
    public function meta(): array
    {
        return array_merge([
            'falseValue' => $this->falseValue,
            'trueValue' => $this->trueValue,
        ], $this->meta);
    }
}
