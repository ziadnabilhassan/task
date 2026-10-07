<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company) {
            User::factory()
                ->admin()
                ->create([
                    'company_id' => $company->id,
                    'name' => 'Admin ' . $company->name,
                    'email' => 'admin' . $company->id . '@example.com',
                ]);

            User::factory()
                ->manager()
                ->create([
                    'company_id' => $company->id,
                    'name' => 'Manager ' . $company->name,
                    'email' => 'manager' . $company->id . '@example.com',
                ]);

            User::factory()
                ->count(3)
                ->regularUser()
                ->create([
                    'company_id' => $company->id,
                ]);
        });
    }
}
