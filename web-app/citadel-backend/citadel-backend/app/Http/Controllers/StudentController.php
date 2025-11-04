<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'program', 'year', 'section']);
        $perPage = $request->input('per_page', 10);

        $query = Student::with(['program', 'yearSection']);

        // 🔍 Dynamic filtering
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('student_no', 'ILIKE', "%{$search}%")
                  ->orWhere('fullname', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // ✅ Filter by program name
        if (!empty($filters['program'])) {
            $query->whereHas('program', function($q) use ($filters) {
                $q->where('program_name', $filters['program']);
            });
        }

        // ✅ Filter by year level
        if (!empty($filters['year'])) {
            $query->whereHas('yearSection', function($q) use ($filters) {
                $q->where('year_level', $filters['year']);
            });
        }

        // ✅ Filter by section
        if (!empty($filters['section'])) {
            $query->whereHas('yearSection', function($q) use ($filters) {
                $q->where('section', $filters['section']);
            });
        }

        $students = $query->latest()->paginate($perPage);

        // ✅ Transform response to include relationship data
        $students->getCollection()->transform(function($student) {
            return [
                'id' => $student->id,
                'fullname' => $student->fullname,
                'studentNo' => $student->student_no, // camelCase for frontend
                'student_no' => $student->student_no, // snake_case for consistency
                'program_id' => $student->program_id,
                'year_section_id' => $student->year_section_id,
                'program' => $student->program->program_name ?? '', // From relationship
                'year' => $student->yearSection->year_level ?? '', // From relationship
                'section' => $student->yearSection->section ?? '', // From relationship
                'status' => $student->status ?? '',
                'dob' => $student->dob,
                'gender' => $student->gender,
                'email' => $student->email,
                'contact' => $student->contact,
                'address' => $student->address,
                'guardianName' => $student->guardian_name, // camelCase for frontend
                'guardianContact' => $student->guardian_contact, // camelCase for frontend
                'guardianAddress' => $student->guardian_address, // camelCase for frontend
                'username' => $student->username,
                'created_at' => $student->created_at,
            ];
        });

        return response()->json($students);
    }

    public function store(Request $request)
    {
         // ✅ ACCEPT SNAKE_CASE FROM FRONTEND
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'student_no' => 'required|string|unique:students,student_no', // snake_case
            'program_id' => 'required|exists:programs,id',
            'year_section_id' => 'required|exists:year_sections,id',
            'status' => 'required|string|in:Regular,Irregular',
            'dob' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'email' => 'required|email|unique:students,email',
            'contact' => 'required|string|max:15',
            'address' => 'required|string',
            'guardian_name' => 'required|string|max:255', // snake_case
            'guardian_contact' => 'required|string|max:15', // snake_case
            'guardian_email' => 'nullable|email', // guardian email
            'guardian_address' => 'required|string', // snake_case
            'username' => 'required|string|unique:students,username',
            'password' => 'required|string|min:8',
        ]);

        // ✅ NO NEED TO MAP - DIRECT ASSIGNMENT
        $payload = [
            'fullname' => $validated['fullname'],
            'student_no' => $validated['student_no'],
            'program_id' => $validated['program_id'],
            'year_section_id' => $validated['year_section_id'],
            'status' => $validated['status'],
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardian_name'],
            'guardian_contact' => $validated['guardian_contact'],
            'guardian_email' => $validated['guardian_email'] ?? null,
            'guardian_address' => $validated['guardian_address'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ];

        // ✅ Check for required fields
        if (!$payload['student_no']) {
            return response()->json([
                'message' => 'Student number is required',
                'errors' => ['studentNo' => ['Student number is required']]
            ], 422);
        }

        if (!$payload['guardian_name']) {
            return response()->json([
                'message' => 'Guardian name is required',
                'errors' => ['guardianName' => ['Guardian name is required']]
            ], 422);
        }

        $student = Student::create($payload);

        // Load relationships for response
        $student->load(['program', 'yearSection']);

        return response()->json([
            'message' => 'Student created successfully!',
            'data' => $student
        ], 201);
    }

    public function show($id)
    {
        $student = Student::with(['program', 'yearSection'])->findOrFail($id);
        
        // ✅ Consistent field names for frontend
        $transformed = [
            'id' => $student->id,
            'fullname' => $student->fullname,
            'studentNo' => $student->student_no,
            'student_no' => $student->student_no,
            'program_id' => $student->program_id,
            'year_section_id' => $student->year_section_id,
            'section' => $student->yearSection->section ?? '',
            'program' => $student->program->program_name ?? '',
            'year' => $student->yearSection->year_level ?? '',
            'dob' => $student->dob,
            'gender' => $student->gender,
            'email' => $student->email,
            'contact' => $student->contact,
            'address' => $student->address,
            'guardianName' => $student->guardian_name,
            'guardianContact' => $student->guardian_contact, // ✅ FIXED: was 'guardian_cntact'
            'guardian_email' => $student->guardian_email,
            'guardianAddress' => $student->guardian_address,
            'username' => $student->username,
            'created_at' => $student->created_at,
        ];

        return response()->json($transformed);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        // ✅ Accept both camelCase and snake_case
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'studentNo' => 'required|string|unique:students,student_no,' . $id,
            'student_no' => 'sometimes|string|unique:students,student_no,' . $id,
            'program_id' => 'required|exists:programs,id',
            'year_section_id' => 'required|exists:year_sections,id',
            'status' => 'required|string|in:Regular,Irregular',
            'dob' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'email' => 'required|email|unique:students,email,' . $id,
            'contact' => 'required|string|max:15',
            'address' => 'required|string',
            'guardianName' => 'required|string|max:255',
            'guardian_name' => 'sometimes|string|max:255',
            'guardianContact' => 'required|string|max:15',
            'guardian_contact' => 'sometimes|string|max:15',
            'guardian_email' => 'nullable|email',
            'guardianEmail' => 'sometimes|email',
            'guardianAddress' => 'required|string',
            'guardian_address' => 'sometimes|string',
            'username' => 'required|string|unique:students,username,' . $id,
            'password' => 'nullable|string|min:8',
        ]);

        // ✅ Handle both camelCase and snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'student_no' => $validated['studentNo'] ?? $validated['student_no'] ?? $student->student_no,
            'program_id' => $validated['program_id'],
            'year_section_id' => $validated['year_section_id'],
            'status' => $validated['status'],
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardianName'] ?? $validated['guardian_name'] ?? $student->guardian_name,
            'guardian_contact' => $validated['guardianContact'] ?? $validated['guardian_contact'] ?? $student->guardian_contact,
            'guardian_email' => $validated['guardian_email'] ?? $validated['guardianEmail'] ?? $student->guardian_email,
           
            'guardian_address' => $validated['guardianAddress'] ?? $validated['guardian_address'] ?? $student->guardian_address,
            'username' => $validated['username'],
        ];

        // Update password only if provided
        if ($request->filled('password')) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $student->update($payload);
        $student->load(['program', 'yearSection']);

        return response()->json([
            'message' => 'Student updated successfully!',
            'data' => [
                'id' => $student->id,
                'fullname' => $student->fullname,
                'studentNo' => $student->student_no,
                'program' => $student->program->program_name ?? '',
                'year' => $student->yearSection->year_level ?? '',
                'section' => $student->yearSection->section ?? '',
                'email' => $student->email,
                'username' => $student->username,
            ]
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        try {
            $ids = $request->input('ids');

            if (!$ids || !is_array($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No student IDs provided'
                ], 400);
            }

            $deletedCount = Student::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "$deletedCount student(s) deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete students',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $student = Student::find($id);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get students by section (legacy method - kept for backward compatibility)
    public function getStudentsBySection(Request $request)
    {
        $section = $request->get('section');
        $program = $request->get('program');
        $year = $request->get('year');

        $query = Student::with(['program', 'yearSection']);

        if ($section) {
            $query->whereHas('yearSection', function($q) use ($section) {
                $q->where('section', $section);
            });
        }

        if ($program) {
            // Support both program_id and program_name
            if (is_numeric($program)) {
                $query->where('program_id', $program);
            } else {
                $query->whereHas('program', function($q) use ($program) {
                    $q->where('program_name', $program);
                });
            }
        }

        if ($year) {
            $query->whereHas('yearSection', function($q) use ($year) {
                $q->where('year_level', $year);
            });
        }

        $students = $query->orderBy('fullname')->get();

        $students->transform(function($s) {
            return [
                'id' => $s->id,
                'fullname' => $s->fullname,
                'studentNo' => $s->student_no,
                'section' => $s->yearSection->section ?? 'N/A',
                'program' => $s->program->program_name ?? 'N/A',
                'year' => $s->yearSection->year_level ?? 'N/A',
                'email' => $s->email,
                'contact' => $s->contact,
            ];
        });

        return response()->json($students);
    }

    // Get students by program_id, year_level, and section (for faculty loads)
    public function getStudentsByFacultyLoad(Request $request)
    {
        $programId = $request->get('program_id');
        $yearLevel = $request->get('year_level');
        $section = $request->get('section');
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');

        $query = Student::with(['program', 'yearSection']);

        // Filter by program_id
        if ($programId) {
            $query->where('program_id', $programId);
        }

        // Filter by year_level and section through yearSection relationship
        if ($yearLevel || $section) {
            $query->whereHas('yearSection', function($q) use ($yearLevel, $section) {
                if ($yearLevel) {
                    // Handle both numeric and text year levels (e.g., "4" or "Fourth Year")
                    // Convert text to number for comparison
                    $yearLevelNum = $yearLevel;
                    if (!is_numeric($yearLevel)) {
                        $yearMap = [
                            'first year' => '1', 'first' => '1', '1st year' => '1', '1st' => '1',
                            'second year' => '2', 'second' => '2', '2nd year' => '2', '2nd' => '2',
                            'third year' => '3', 'third' => '3', '3rd year' => '3', '3rd' => '3',
                            'fourth year' => '4', 'fourth' => '4', '4th year' => '4', '4th' => '4',
                            'fifth year' => '5', 'fifth' => '5', '5th year' => '5', '5th' => '5'
                        ];
                        $yearLower = strtolower(trim($yearLevel));
                        $yearLevelNum = $yearMap[$yearLower] ?? $yearLevel;
                    }
                    
                    // Match both numeric and text formats
                    $q->where(function($subQ) use ($yearLevelNum, $yearLevel) {
                        $subQ->where('year_level', $yearLevelNum)
                             ->orWhere('year_level', $yearLevel)
                             ->orWhere('year_level', 'like', '%' . $yearLevelNum . '%');
                    });
                }
                if ($section) {
                    // Remove suffixes like "West", "North", etc. for matching
                    $cleanSection = preg_replace('/\s*-\s*(West|North|East|South)$/i', '', $section);
                    // Match exact or section with suffix
                    $q->where(function($subQ) use ($section, $cleanSection) {
                        $subQ->where('section', $section)
                             ->orWhere('section', $cleanSection)
                             ->orWhere('section', 'like', $cleanSection . '%');
                    });
                }
            });
        }

        $students = $query->orderBy('fullname')->get();

        $students->transform(function($s) {
            return [
                'id' => $s->id,
                'fullname' => $s->fullname,
                'studentNo' => $s->student_no,
                'section' => $s->yearSection->section ?? 'N/A',
                'program' => $s->program->program_name ?? 'N/A',
                'program_id' => $s->program_id,
                'year' => $s->yearSection->year_level ?? 'N/A',
                'year_section_id' => $s->year_section_id,
                'email' => $s->email,
                'contact' => $s->contact,
            ];
        });

        return response()->json($students);
    }

    // Get all unique sections
    public function getAllSections(Request $request)
    {
        $sections = Student::distinct()
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->pluck('section')
            ->sort()
            ->values();

        return response()->json($sections);
    }

    // Register facial recognition for a student
    public function registerFacialRecognition(Request $request, $id)
    {
        $student = Student::find($id);
        
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Update facial recognition status
        $student->has_facial_recognition = true;
        
        // Optionally store facial recognition data if provided
        if ($request->has('facial_data')) {
            $student->facial_recognition_data = $request->facial_data;
        }
        
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Facial recognition registered successfully',
            'student' => [
                'id' => $student->id,
                'fullname' => $student->fullname,
                'has_facial_recognition' => $student->has_facial_recognition,
            ]
        ]);
    }
}