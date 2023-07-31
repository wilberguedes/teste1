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

trait HasSections
{
    /**
     * Registered sections
     */
    protected static array $sections = [];

    /**
     * Register new section.
     */
    public static function registerSection(Section $section): void
    {
        if (! static::findSection($section->id)) {
            static::$sections[] = $section;
        }
    }

    /**
     * Register multiple sections.
     */
    public static function registerSections(array $sections): void
    {
        foreach ($sections as $section) {
            static::registerSection($section);
        }
    }

    /**
     * Find tab by given ID.
     */
    public static function findSection(string $id): ?Section
    {
        foreach (static::$sections as $registered) {
            if ($registered->id === $id) {
                return $registered;
            }
        }

        return null;
    }

    /**
     * Merge the sections options with the given ones section
     */
    public function mergeSections(array $settings): array
    {
        $settings = collect($settings);

        return collect(static::$sections)->map(function ($section) use ($settings) {
            if ($option = $settings->firstWhere('id', $section->id)) {
                $section->enabled = $option['enabled'];
                $section->order = $option['order'];
            }

            return $section;
        })->sortBy('order')->values()->all();
    }
}
