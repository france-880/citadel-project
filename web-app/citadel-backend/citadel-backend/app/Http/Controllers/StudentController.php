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
            'dob' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'email' => 'required|email|unique:students,email',
            'contact' => 'required|string|max:15',
            'address' => 'required|string',
            'guardian_name' => 'required|string|max:255', // snake_case
            'guardian_contact' => 'required|string|max:15', // snake_case
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
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardian_name'],
            'guardian_contact' => $validated['guardian_contact'],
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
            'dob' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'email' => 'required|email|unique:students,email,' . $id,
            'contact' => 'required|string|max:15',
            'address' => 'required|string',
            'guardianName' => 'required|string|max:255',
            'guardian_name' => 'sometimes|string|max:255',
            'guardianContact' => 'required|string|max:15',
            'guardian_contact' => 'sometimes|string|max:15',
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
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardianName'] ?? $validated['guardian_name'] ?? $student->guardian_name,
            'guardian_contact' => $validated['guardianContact'] ?? $validated['guardian_contact'] ?? $student->guardian_contact,
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
}