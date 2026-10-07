<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company) {
            $users = User::query()
                ->where('company_id', $company->id)
                ->get();

            $users->each(function (User $user) {
                Task::factory()
                    ->pending()
                    ->highPriority()
                    ->create([
                        'company_id' => $user->company_id,
                        'assigned_to' => $user->id,
                        'title' => 'Pending task for ' . $user->name,
                    ]);

                Task::factory()
                    ->inProgress()
                    ->mediumPriority()
                    ->create([
                        'company_id' => $user->company_id,
                        'assigned_to' => $user->id,
                        'title' => 'In progress task for ' . $user->name,
                    ]);

                Task::factory()
                    ->completed()
                    ->lowPriority()
                    ->create([
                        'company_id' => $user->company_id,
                        'assigned_to' => $user->id,
                        'title' => 'Completed task for ' . $user->name,
                    ]);
            });

            Task::factory()
                ->count(5)
                ->create([
                    'company_id' => $company->id,
                    'assigned_to' => $users->random()->id,
                ]);
        });
    }
}
