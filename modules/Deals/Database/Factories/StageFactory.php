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

namespace Modules\Deals\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;

class StageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Stage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->catchPhrase(),
            'win_probability' => 50,
            'pipeline_id' => Pipeline::factory(),
        ];
    }
}
