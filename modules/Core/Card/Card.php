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

namespace Modules\Core\Card;

use Illuminate\Support\Str;
use JsonSerializable;
use Modules\Core\Authorizeable;
use Modules\Core\HasHelpText;
use Modules\Core\Makeable;
use Modules\Core\MetableElement;
use Modules\Core\RangedElement;

// @ todo, add Authorizeable tests and general test

abstract class Card extends RangedElement implements JsonSerializable
{
    use Makeable, Authorizeable, MetableElement, HasHelpText;

    /**
     * The card name/title that will be displayed
     */
    public ?string $name = null;

    /**
     * Explanation about the card data
     */
    public ?string $description = null;

    /**
     * The width of the card (Tailwind width class e.q. w-1/2, w-1/3, w-full)
     */
    public string $width = 'w-full lg:w-1/2';

    /**
     * Indicates that the card should be shown only dashboard
     */
    public bool $onlyOnDashboard = false;

    /**
     * Indicates that the card should be shown only on index
     */
    public bool $onlyOnIndex = false;

    /**
     * Indicates that the card should refreshed when action is executed
     */
    public bool $refreshOnActionExecuted = false;

    /**
     * Indicates whether user can be selected
     *
     * @var bool|int|callable
     */
    public mixed $withUserSelection = false;

    /**
     * Define the card component used on front end
     */
    abstract public function component(): string;

    /**
     * The card human readable name
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get the card explanation
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Set that the card should be shown only dashboard
     */
    public function onlyOnDashboard(): static
    {
        $this->onlyOnDashboard = true;

        return $this;
    }

    /**
     * Set that the card should be shown only on index
     */
    public function onlyOnIndex(): static
    {
        $this->onlyOnIndex = true;

        return $this;
    }

    /**
     * Get the URI key for the card.
     */
    public function uriKey(): string
    {
        return Str::kebab(class_basename(get_called_class()));
    }

    /**
     * Set the card width class
     */
    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set refresh on action executed for the card
     */
    public function refreshOnActionExecuted(bool $value = true): static
    {
        $this->refreshOnActionExecuted = $value;

        return $this;
    }

    /**
     * Set card user selection
     */
    public function withUserSelection(bool|int|callable $value = true): static
    {
        $this->withUserSelection = $value;

        return $this;
    }

    /**
     * Get the users for selection
     *
     * @return array|\Illuminate\Support\Collection
     */
    public function users()
    {
        //
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'uriKey' => $this->uriKey(),
            'component' => $this->component(),
            'name' => $this->name(),
            'description' => $this->description(),
            'width' => $this->width,
            'withUserSelection' => is_callable($this->withUserSelection) ?
                call_user_func($this->withUserSelection, $this) :
                $this->withUserSelection,
            'users' => $this->users(),
            'refreshOnActionExecuted' => $this->refreshOnActionExecuted,
            'helpText' => $this->helpText,
            'data' => method_exists($this, 'getData') ? $this->getData() : [],
        ], $this->meta());
    }
}
