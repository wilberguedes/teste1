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

namespace Modules\Core\Highlights;

class Highlights
{
    /**
     * @var array<Highlight>
     */
    protected static array $highlights = [];

    /**
     * Get all the highlights.
     *
     * @return array<Highlight>
     */
    public static function get(): array
    {
        return array_values(static::$highlights);
    }

    /**
     * Register new highlight.
     *
     * @param  Highlight|array<Highlight>  $highlights
     */
    public static function register(Highlight|array $highlights): static
    {
        if (! is_array($highlights)) {
            $highlights = [$highlights];
        }

        foreach ($highlights as $highlight) {
            if (! $highlight instanceof Highlight) {
                $highlight = new $highlight;
            }

            static::$highlights[$highlight->name()] = $highlight;
        }

        return new static;
    }
}
