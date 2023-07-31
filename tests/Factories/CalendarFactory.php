<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Calendar;

class CalendarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Calendar::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->paragraph,
            'user_id' => null,
        ];
    }
}
