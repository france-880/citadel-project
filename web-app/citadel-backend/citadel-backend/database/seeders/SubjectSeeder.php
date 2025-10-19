<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AcademicManagement\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample subjects
        $subjects = [
            [
                'subject_name' => 'Introduction to Computer Science',
                'subject_code' => 'CS101',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Data Structures and Algorithms',
                'subject_code' => 'CS102',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Database Management Systems',
                'subject_code' => 'CS103',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Web Development',
                'subject_code' => 'CS104',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Software Engineering',
                'subject_code' => 'CS105',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Computer Networks',
                'subject_code' => 'CS106',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Operating Systems',
                'subject_code' => 'CS107',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Information Technology Fundamentals',
                'subject_code' => 'IT101',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'System Analysis and Design',
                'subject_code' => 'IT102',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Network Administration',
                'subject_code' => 'IT103',
                'subject_type' => 'Major'
            ],
            [
                'subject_name' => 'Mathematics in the Modern World',
                'subject_code' => 'MATH101',
                'subject_type' => 'General Education'
            ],
            [
                'subject_name' => 'English Communication',
                'subject_code' => 'ENG101',
                'subject_type' => 'General Education'
            ],
            [
                'subject_name' => 'Philippine History',
                'subject_code' => 'HIST101',
                'subject_type' => 'General Education'
            ],
            [
                'subject_name' => 'Ethics',
                'subject_code' => 'ETHICS101',
                'subject_type' => 'General Education'
            ],
            [
                'subject_name' => 'Art Appreciation',
                'subject_code' => 'ART101',
                'subject_type' => 'General Education'
            ]
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        $this->command->info('Sample subjects created successfully!');
    }
}