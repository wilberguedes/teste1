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

namespace Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Documents\Models\DocumentType::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'swatch_color' => $this->faker->hexColor(),
        ];
    }

    /**
     * Indicate that the type is primary.
     */
    public function primary(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'flag' => Str::snake($attributes['name']),
            ];
        });
    }
}
