<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgeGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ageRanges = ['3-5', '6-9', '10-13', '14+'];

        foreach ($ageRanges as $range) {
            DB::table('age_groups')->insert([
                'age_range' => $range,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
