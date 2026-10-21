<?php

namespace Database\Factories;

use App\Models\Athlete;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Athlete>
 */
class AthleteFactory extends Factory
{
    protected $model = Athlete::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name'       => fake()->name(),
            'birth_date'      => fake()->date('Y-m-d', '2010-01-01'),
            'marital_status'  => fake()->randomElement(['solteiro', 'casado', 'divorciado', 'viuvo']),
            'gender'          => fake()->randomElement(['masculino', 'feminino', 'outro']),
            'position'        => fake()->randomElement(['Goleiro', 'Zagueiro', 'Lateral', 'Volante', 'Meia', 'Atacante']),
            'document'        => fake()->unique()->numerify('###########'), // 11 dígitos únicos
            'address'         => fake()->streetName(),
            'number'          => (string) fake()->numberBetween(1, 9999),
            'neighborhood'    => fake()->citySuffix(),
            'zip_code'        => fake()->numerify('########'),
            'state'           => 'SP',
            'city'            => fake()->city(),
            'mobile_phone'    => fake()->numerify('119########'),
            'secondary_phone' => null,
            'email'           => fake()->unique()->safeEmail(),
            'mother_name'     => fake()->name('female'),
            'father_name'     => fake()->name('male'),
            'observations'    => null,
        ];
    }
}
