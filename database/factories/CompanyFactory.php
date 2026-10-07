<?php

namespace Database\Factories;

use App\Models\Company;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();

        return [
            'name' => $faker->unique()->company(),
        ];
    }
}
