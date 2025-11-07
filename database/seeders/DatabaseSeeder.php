<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StatesTableSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User']
        );

        CustomerModel::factory(20)->create();
    }
}
