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

namespace Modules\Core\Fields;

trait ChangesKeys
{
    /**
     * From where the value key should be taken
     */
    public string $valueKey = 'value';

    /**
     * From where the label key should be taken
     */
    public string $labelKey = 'label';

    /**
     * Set custom key for value.
     */
    public function valueKey(string $key): static
    {
        $this->valueKey = $key;

        return $this;
    }

    /**
     * Set custom label key.
     */
    public function labelKey(string $key): static
    {
        $this->labelKey = $key;

        return $this;
    }
}
