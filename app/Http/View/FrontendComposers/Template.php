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

namespace App\Http\View\FrontendComposers;

use JsonSerializable;

class Template implements JsonSerializable
{
    /**
     * @var \App\Http\View\FrontendComposers\Component|null
     */
    public ?Component $viewComponent = null;

    /**
     * Set the view component instance.
     */
    public function viewComponent(Component $component): static
    {
        $this->viewComponent = $component;

        return $this;
    }

    /**
     * Prepare the template for front-end
     */
    public function jsonSerialize(): array
    {
        return [
            'view' => $this->viewComponent,
        ];
    }
}
