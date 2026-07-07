<?php

namespace Database\Factories;

use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Blueprint>
 */
class BlueprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'name'           => $this->faker->words(3, true),
            'tone'           => 'professionnel mais décontracté',
            'max_hashtags'   => 1,
            'max_characters' => 280,
            'regle_supp'     => 'Ne jamais utiliser d\'emojis',
        ];
    }
}
