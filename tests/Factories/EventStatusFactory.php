<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\EventStatus;

class EventStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->paragraph,
        ];
    }
}
