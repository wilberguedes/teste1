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

namespace Modules\Activities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Activities\Models\ActivityType;

class ActivityTypeFactory extends Factory
{
    /**
     * All time used icons
     */
    protected static array $usedIcons = [];

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'swatch_color' => $this->faker->hexColor(),
            'icon' => $this->getIcon(),
        ];
    }

    /**
     * Indicate that the type is primary.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'flag' => Str::snake($attributes['name']),
            ];
        });
    }

    /**
     * Get icon for the type
     *
     * @return string
     */
    protected function getIcon()
    {
        $icons = collect($this->icons());
        $icon = null;

        do {
            if (count(static::$usedIcons) === $icons->count()) {
                $icon = $this->faker->unique()->word();
            } else {
                $randomIcon = $icons->random();

                if (! in_array($randomIcon, static::$usedIcons)) {
                    $icon = $randomIcon;

                    static::$usedIcons[] = $icon;
                }
            }

            if (ActivityType::where('icon', $icon)->exists()) {
                $icon = null;
            }
        } while (! $icon);

        return $icon;
    }

    /**
     * Get the available factory icons
     *
     * @return array
     */
    protected function icons()
    {
        return [
            'Mail',
            'PencilAlt',
            'OfficeBuilding',
            'Phone',
            'Calendar',
            'Collection',
            'Bell',
            'AtSymbol',
            'Briefcase',
            'Chat',
            'CheckCircle',
            'BookOpen',
            'Camera',
            'Truck',
            'Folder',
            'DeviceMobile',
            'Users',
            'ChatAlt',
            'Clock',
        ];
    }
}
