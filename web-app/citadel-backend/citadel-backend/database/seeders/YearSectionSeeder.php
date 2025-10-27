<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\YearSection;

class YearSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        YearSection::create(['year_level' => '1st Year', 'section' => 'A']);
        YearSection::create(['year_level' => '1st Year', 'section' => 'B']);
        YearSection::create(['year_level' => '1st Year', 'section' => 'C']);
        YearSection::create(['year_level' => '2nd Year', 'section' => 'A']);
        YearSection::create(['year_level' => '2nd Year', 'section' => 'B']);
        YearSection::create(['year_level' => '2nd Year', 'section' => 'C']);
        YearSection::create(['year_level' => '3rd Year', 'section' => 'A']);
        YearSection::create(['year_level' => '3rd Year', 'section' => 'B']);
        YearSection::create(['year_level' => '3rd Year', 'section' => 'C']);
        YearSection::create(['year_level' => '4th Year', 'section' => 'A']);
        YearSection::create(['year_level' => '4th Year', 'section' => 'B']);
        YearSection::create(['year_level' => '4th Year', 'section' => 'C']);
    }
}
