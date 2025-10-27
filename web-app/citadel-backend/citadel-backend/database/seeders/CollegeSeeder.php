<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AcademicManagement\College;

class CollegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colleges = [
            [
                'college_name' => 'College of Engineering',
                'college_code' => 'COE',
                'dean_id' => null, // I-set mo na lang later kapag may dean accounts na
            ],
            [
                'college_name' => 'College of Computer Studies',
                'college_code' => 'CCS',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Business Administration',
                'college_code' => 'CBA',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Education',
                'college_code' => 'COED',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Arts and Sciences',
                'college_code' => 'CAS',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Nursing',
                'college_code' => 'CON',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Criminal Justice Education',
                'college_code' => 'CCJE',
                'dean_id' => null,
            ],
            [
                'college_name' => 'College of Hospitality Management',
                'college_code' => 'CHM',
                'dean_id' => null,
            ]
        ];

        foreach ($colleges as $college) {
            College::create($college);
        }

        // Or using createMany (mas efficient)
        // College::insert($colleges);
    }
}