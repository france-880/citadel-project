<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\Program;
use App\Models\AcademicManagement\College;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = Program::with(['college', 'programHead', 'subjects'])->get();
        return response()->json([
            'success' => true,
            'data' => $programs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|unique:programs,program_code|max:10',
            'college_id' => 'required|exists:colleges,id',
            'program_head_id' => 'nullable|exists:accounts,id'
        ]);

        $program = Program::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Program created successfully',
            'data' => $program->load(['college', 'programHead'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $program = Program::with(['college', 'programHead'])->find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $program
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $program = Program::find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        $request->validate([
            'program_name' => 'sometimes|required|string|max:255',
            'program_code' => 'sometimes|required|string|unique:programs,program_code,' . $id . '|max:10',
            'college_id' => 'sometimes|required|exists:colleges,id',
            'program_head_id' => 'nullable|exists:accounts,id'
        ]);

        $program->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Program updated successfully',
            'data' => $program->load(['college', 'programHead'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $program = Program::find($id);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        // Add any additional checks here (e.g., if program has students)
        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program deleted successfully'
        ]);
    }

    /**
     * Get programs by college
     */
    public function getByCollege($collegeId)
    {
        $programs = Program::with('programHead')
            ->where('college_id', $collegeId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programs
        ]);
    }

    /**
     * Get available program heads for assignment
     */
    public function getAvailableProgramHeads()
    {
        $programHeads = Account::where('role', 'program_head')
            ->whereDoesntHave('programAsHead')
            ->get(['id', 'fullname', 'email']);

        return response()->json([
            'success' => true,
            'data' => $programHeads
        ]);
    }

    /**
     * Assign a subject to a program
     */
    public function assignSubject(Request $request, $programId)
    {
        $program = Program::find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester' => 'nullable|string',
            'year_level' => 'nullable|string'
        ]);

        // Check if subject is already assigned with the same semester and year_level
        $existingAssignment = $program->subjects()
            ->where('subject_id', $request->subject_id)
            ->wherePivot('semester', $request->semester)
            ->wherePivot('year_level', $request->year_level)
            ->exists();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is already assigned to this program with the same semester and year level'
            ], 400);
        }

        // Prepare pivot data
        $pivotData = [];
        if ($request->has('semester')) {
            $pivotData['semester'] = $request->semester;
        }
        if ($request->has('year_level')) {
            $pivotData['year_level'] = $request->year_level;
        }

        // Assign the subject with pivot data
        $program->subjects()->attach($request->subject_id, $pivotData);

        return response()->json([
            'success' => true,
            'message' => 'Subject assigned successfully',
            'data' => $program->load(['college', 'programHead', 'subjects'])
        ]);
    }

    /**
     * Update pivot data (year_level and semester) for an existing subject assignment
     */
    public function updateSubjectAssignment(Request $request, $programId, $subjectId)
    {
        $program = Program::find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        // Check if subject is assigned
        if (!$program->subjects()->where('subject_id', $subjectId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is not assigned to this program'
            ], 400);
        }

        $request->validate([
            'semester' => 'nullable|string',
            'year_level' => 'nullable|string'
        ]);

        // Prepare pivot data to update
        $pivotData = [];
        if ($request->has('semester')) {
            $pivotData['semester'] = $request->semester;
        }
        if ($request->has('year_level')) {
            $pivotData['year_level'] = $request->year_level;
        }

        if (empty($pivotData)) {
            return response()->json([
                'success' => false,
                'message' => 'No data to update. Please provide semester or year_level.'
            ], 400);
        }

        // Update the pivot data
        $program->subjects()->updateExistingPivot($subjectId, $pivotData);

        return response()->json([
            'success' => true,
            'message' => 'Subject assignment updated successfully',
            'data' => $program->load(['college', 'programHead', 'subjects'])
        ]);
    }

    /**
     * Unassign a subject from a program
     */
    public function unassignSubject($programId, $subjectId)
    {
        $program = Program::find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        // Check if subject is assigned
        if (!$program->subjects()->where('subject_id', $subjectId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is not assigned to this program'
            ], 400);
        }

        // Unassign the subject
        $program->subjects()->detach($subjectId);

        return response()->json([
            'success' => true,
            'message' => 'Subject unassigned successfully',
            'data' => $program->load(['college', 'programHead', 'subjects'])
        ]);
    }

    /**
     * Get all subjects assigned to a program
     * Optionally filter by year_level and semester
     */
    public function getSubjects(Request $request, $programId)
    {
        $program = Program::find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        // Get year_level and semester from request (optional filters)
        $yearLevel = $request->get('year_level');
        $semester = $request->get('semester');

        \Log::info('ProgramController::getSubjects - Filters', [
            'program_id' => $programId,
            'year_level' => $yearLevel,
            'semester' => $semester
        ]);

        // Get all subjects first, then filter by pivot columns
        $allSubjects = $program->subjects()->get();
        
        // Log ALL subjects with their pivot data BEFORE filtering
        \Log::info('ProgramController::getSubjects - ALL Subjects BEFORE Filtering', [
            'total_all_subjects' => $allSubjects->count(),
            'program_id' => $programId,
            'all_subjects_pivot_data' => $allSubjects->map(function($s) {
                return [
                    'id' => $s->id,
                    'code' => $s->subject_code,
                    'name' => $s->subject_name,
                    'pivot_year_level' => $s->pivot->year_level ?? 'NULL',
                    'pivot_semester' => $s->pivot->semester ?? 'NULL',
                    'pivot_year_level_type' => gettype($s->pivot->year_level ?? null),
                    'pivot_semester_type' => gettype($s->pivot->semester ?? null),
                ];
            })
        ]);
        
        // Check if there are ANY subjects at all
        if ($allSubjects->isEmpty()) {
            \Log::warning('ProgramController::getSubjects - No subjects found for program', [
                'program_id' => $programId,
                'program_name' => $program->program_name ?? 'N/A'
            ]);
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No subjects assigned to this program yet.'
            ]);
        }
        
        // Filter by year_level if provided
        $normalizedYearLevels = [];
        if ($yearLevel && $yearLevel !== '') {
            $normalizedYearLevels = $this->normalizeYearLevel($yearLevel);
            \Log::info('Filtering by year_level:', [
                'input_year_level' => $yearLevel,
                'normalized_formats' => $normalizedYearLevels,
                'count' => count($normalizedYearLevels)
            ]);
        }

        // Filter by semester if provided
        $normalizedSemesters = [];
        if ($semester && $semester !== '') {
            $normalizedSemesters = $this->normalizeSemester($semester);
            \Log::info('Filtering by semester:', [
                'input_semester' => $semester,
                'normalized_formats' => $normalizedSemesters,
                'count' => count($normalizedSemesters)
            ]);
        }

        // Filter subjects in PHP based on pivot data
        // Use case-insensitive and trimmed comparison
        $subjects = $allSubjects->filter(function($subject) use ($normalizedYearLevels, $normalizedSemesters) {
            $pivotYearLevel = trim($subject->pivot->year_level ?? '');
            $pivotSemester = trim($subject->pivot->semester ?? '');
            
            // If pivot data is empty/null, skip this subject (no match)
            if (empty($pivotYearLevel) && !empty($normalizedYearLevels)) {
                \Log::info('Subject skipped - no year_level:', ['subject_id' => $subject->id, 'code' => $subject->subject_code]);
                return false;
            }
            if (empty($pivotSemester) && !empty($normalizedSemesters)) {
                \Log::info('Subject skipped - no semester:', ['subject_id' => $subject->id, 'code' => $subject->subject_code]);
                return false;
            }
            
            // Check year_level match (case-insensitive)
            $yearLevelMatch = empty($normalizedYearLevels);
            if (!empty($normalizedYearLevels) && !empty($pivotYearLevel)) {
                foreach ($normalizedYearLevels as $normalized) {
                    if (strcasecmp(trim($pivotYearLevel), trim($normalized)) === 0) {
                        $yearLevelMatch = true;
                        break;
                    }
                }
            }
            
            // Check semester match (case-insensitive)
            $semesterMatch = empty($normalizedSemesters);
            if (!empty($normalizedSemesters) && !empty($pivotSemester)) {
                foreach ($normalizedSemesters as $normalized) {
                    if (strcasecmp(trim($pivotSemester), trim($normalized)) === 0) {
                        $semesterMatch = true;
                        break;
                    }
                }
            }
            
            // Log each subject being checked
            \Log::info('Checking subject:', [
                'subject_id' => $subject->id,
                'subject_code' => $subject->subject_code,
                'pivot_year_level' => $pivotYearLevel ?: 'NULL',
                'pivot_semester' => $pivotSemester ?: 'NULL',
                'normalized_year_levels' => $normalizedYearLevels,
                'normalized_semesters' => $normalizedSemesters,
                'year_level_match' => $yearLevelMatch,
                'semester_match' => $semesterMatch,
                'final_match' => ($yearLevelMatch && $semesterMatch)
            ]);
            
            return $yearLevelMatch && $semesterMatch;
        })->values(); // Re-index the collection
        
        \Log::info('ProgramController::getSubjects - Results', [
            'total_all_subjects' => $allSubjects->count(),
            'total_filtered_subjects' => $subjects->count(),
            'filter_applied' => [
                'year_level' => $yearLevel ?? 'none',
                'semester' => $semester ?? 'none',
                'normalized_year_levels' => $normalizedYearLevels,
                'normalized_semesters' => $normalizedSemesters
            ],
            'filtered_subjects' => $subjects->map(function($s) {
                return [
                    'id' => $s->id,
                    'code' => $s->subject_code,
                    'pivot_semester' => $s->pivot->semester ?? null,
                    'pivot_year_level' => $s->pivot->year_level ?? null
                ];
            })
        ]);
        
        // If no subjects found after filtering, check if it's because of NULL values
        if ($subjects->isEmpty() && $yearLevel && $semester) {
            $subjectsWithNullYearLevel = $allSubjects->filter(function($s) {
                return empty($s->pivot->year_level);
            });
            $subjectsWithNullSemester = $allSubjects->filter(function($s) {
                return empty($s->pivot->semester);
            });
            
            \Log::warning('ProgramController::getSubjects - No subjects matched filters', [
                'total_subjects' => $allSubjects->count(),
                'subjects_with_null_year_level' => $subjectsWithNullYearLevel->count(),
                'subjects_with_null_semester' => $subjectsWithNullSemester->count(),
                'search_year_level' => $yearLevel,
                'search_semester' => $semester,
                'all_subjects_details' => $allSubjects->map(function($s) {
                    return [
                        'id' => $s->id,
                        'code' => $s->subject_code,
                        'year_level' => $s->pivot->year_level ?? 'NULL',
                        'semester' => $s->pivot->semester ?? 'NULL'
                    ];
                })
            ]);
            
            // Return helpful message if subjects exist but have NULL values
            if ($allSubjects->count() > 0) {
                $message = 'No subjects found matching the filters. ';
                if ($subjectsWithNullYearLevel->count() > 0 || $subjectsWithNullSemester->count() > 0) {
                    $message .= 'Some subjects may not have year_level or semester assigned yet. Please re-assign subjects with the correct year level and semester.';
                }
            }
        }

        // Transform the data to include pivot information
        $transformedSubjects = $subjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'subject_code' => $subject->subject_code,
                'subject_name' => $subject->subject_name,
                'subject_description' => $subject->subject_description ?? null,
                'units' => $subject->units ?? null,
                'subject_type' => $subject->subject_type ?? null,
                'semester' => $subject->pivot->semester ?? null,
                'year_level' => $subject->pivot->year_level ?? null,
            ];
        });

        // If no subjects found, include helpful message
        $response = [
            'success' => true,
            'data' => $transformedSubjects
        ];
        
        if ($transformedSubjects->isEmpty() && $yearLevel && $semester) {
            $hasNullValues = $allSubjects->filter(function($s) {
                return empty($s->pivot->year_level) || empty($s->pivot->semester);
            })->count() > 0;
            
            if ($hasNullValues) {
                $response['message'] = 'No subjects found. Some subjects may not have year_level or semester assigned. Please re-assign subjects with the correct year level and semester.';
                $response['debug_info'] = [
                    'total_subjects_in_program' => $allSubjects->count(),
                    'subjects_with_null_data' => $allSubjects->filter(function($s) {
                        return empty($s->pivot->year_level) || empty($s->pivot->semester);
                    })->count(),
                    'search_filters' => [
                        'year_level' => $yearLevel,
                        'semester' => $semester
                    ]
                ];
            }
        }
        
        return response()->json($response);
    }

    /**
     * Normalize year level to handle different formats
     * Returns array of possible formats to match
     */
    private function normalizeYearLevel($yearLevel)
    {
        $yearLevel = trim($yearLevel);
        $formats = [$yearLevel]; // Always include the original format
        
        // Convert "1st Year" to other formats
        if (preg_match('/^(\d+)(st|nd|rd|th)\s+Year$/i', $yearLevel, $matches)) {
            $number = $matches[1];
            $formats[] = $number;
            $formats[] = "{$number}st Year";
            $formats[] = "{$number}nd Year";
            $formats[] = "{$number}rd Year";
            $formats[] = "{$number}th Year";
            
            // Convert to word format
            $wordMap = ['1' => 'First', '2' => 'Second', '3' => 'Third', '4' => 'Fourth', '5' => 'Fifth'];
            if (isset($wordMap[$number])) {
                $formats[] = $wordMap[$number] . ' Year';
            }
        }
        // Convert "First Year" to other formats
        elseif (preg_match('/^(First|Second|Third|Fourth|Fifth)\s+Year$/i', $yearLevel, $matches)) {
            $word = ucfirst(strtolower($matches[1]));
            $numberMap = ['First' => '1', 'Second' => '2', 'Third' => '3', 'Fourth' => '4', 'Fifth' => '5'];
            if (isset($numberMap[$word])) {
                $number = $numberMap[$word];
                $formats[] = $number;
                $formats[] = "{$number}st Year";
                $formats[] = "{$number}nd Year";
                $formats[] = "{$number}rd Year";
                $formats[] = "{$number}th Year";
            }
        }
        // If it's just a number
        elseif (preg_match('/^\d+$/', $yearLevel)) {
            $number = $yearLevel;
            $formats[] = "{$number}st Year";
            $formats[] = "{$number}nd Year";
            $formats[] = "{$number}rd Year";
            $formats[] = "{$number}th Year";
            
            $wordMap = ['1' => 'First', '2' => 'Second', '3' => 'Third', '4' => 'Fourth', '5' => 'Fifth'];
            if (isset($wordMap[$number])) {
                $formats[] = $wordMap[$number] . ' Year';
            }
        }
        
        return array_unique($formats);
    }

    /**
     * Normalize semester to handle different formats
     * Returns array of possible formats to match
     */
    private function normalizeSemester($semester)
    {
        $semester = trim($semester);
        $formats = [$semester]; // Always include the original format
        
        // Convert "First" to other formats
        if (preg_match('/^(First|Second|Summer)$/i', $semester, $matches)) {
            $word = ucfirst(strtolower($matches[1]));
            $formats[] = $word;
            
            // Add number formats
            $numberMap = ['First' => '1', 'Second' => '2'];
            if (isset($numberMap[$word])) {
                $number = $numberMap[$word];
                $formats[] = "{$number}st Semester";
                $formats[] = "{$number}nd Semester";
                $formats[] = "{$word} Semester";
            }
        }
        // Convert "1st Semester" to other formats
        elseif (preg_match('/^(\d+)(st|nd|rd|th)\s+Semester$/i', $semester, $matches)) {
            $number = $matches[1];
            $formats[] = $number;
            $formats[] = "{$number}st Semester";
            $formats[] = "{$number}nd Semester";
            
            $wordMap = ['1' => 'First', '2' => 'Second'];
            if (isset($wordMap[$number])) {
                $word = $wordMap[$number];
                $formats[] = $word;
                $formats[] = "{$word} Semester";
            }
        }
        // If it's just a number
        elseif (preg_match('/^\d+$/', $semester)) {
            $number = $semester;
            $formats[] = "{$number}st Semester";
            $formats[] = "{$number}nd Semester";
            
            $wordMap = ['1' => 'First', '2' => 'Second'];
            if (isset($wordMap[$number])) {
                $word = $wordMap[$number];
                $formats[] = $word;
                $formats[] = "{$word} Semester";
            }
        }
        
        return array_unique($formats);
    }
}