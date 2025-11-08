<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Infrastructure\State\Persistence\Eloquent\StateModel;

class StatesTableSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $states = [
            ['code' => 'JHR', 'name' => 'Johor'],
            ['code' => 'KDH', 'name' => 'Kedah'],
            ['code' => 'KTN', 'name' => 'Kelantan'],
            ['code' => 'KUL', 'name' => 'Kuala Lumpur'],
            ['code' => 'LBN', 'name' => 'Labuan'],
            ['code' => 'MLK', 'name' => 'Malacca'],
            ['code' => 'NSN', 'name' => 'Negeri Sembilan'],
            ['code' => 'PHG', 'name' => 'Pahang'],
            ['code' => 'PRK', 'name' => 'Perak'],
            ['code' => 'PLS', 'name' => 'Perlis'],
            ['code' => 'PNG', 'name' => 'Penang'],
            ['code' => 'PJY', 'name' => 'Putrajaya'],
            ['code' => 'SBH', 'name' => 'Sabah'],
            ['code' => 'SWK', 'name' => 'Sarawak'],
            ['code' => 'SGR', 'name' => 'Selangor'],
            ['code' => 'TRG', 'name' => 'Terengganu'],
        ];

        foreach ($states as $state) {
            StateModel::query()->updateOrCreate(['code' => $state['code']], $state);
        }
    }
}
