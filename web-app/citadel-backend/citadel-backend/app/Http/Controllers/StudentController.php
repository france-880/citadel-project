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
    
        $query = Student::query();
    
        // 🔍 Dynamic filtering
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('student_no', 'ILIKE', "%{$search}%")
                  ->orWhere('fullname', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }
    
        foreach (['program', 'year', 'section'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
    
        $students = $query->latest()->paginate($perPage);
    
        $students->getCollection()->transform(fn($s) => [
            'id' => $s->id,
            'fullname' => $s->fullname,
            'studentNo' => $s->student_no,
            'section' => $s->section,
            'program' => $s->program,
            'year' => $s->year,
            'dob' => $s->dob,
            'gender' => $s->gender,
            'email' => $s->email,
            'contact' => $s->contact,
            'address' => $s->address,
            'guardianName' => $s->guardian_name,
            'guardianContact' => $s->guardian_contact,
            'guardianAddress' => $s->guardian_address,
            'username' => $s->username,
            'created_at' => $s->created_at,
        ]);
    
        return response()->json($students);
    }
    

    public function store(Request $request)
    {
        // Validate incoming camelCase data from React
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'studentNo' => 'required|string|unique:students,student_no',
            'section' => 'required|string',
            'program' => 'required|string',
            'year' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'email' => 'required|email|unique:students,email',
            'contact' => 'required|string',
            'address' => 'required|string',
            'guardianName' => 'required|string',
            'guardianContact' => 'required|string',
            'guardianAddress' => 'required|string',
            'username' => 'required|string|unique:students,username',
            'password' => 'required|string|min:8',
        ]);

        // Map camelCase → snake_case
        $payload = [
            'fullname' => $validated['fullname'],
            'student_no' => $validated['studentNo'],
            'section' => $validated['section'],
            'program' => $validated['program'],
            'year' => $validated['year'],
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardianName'],
            'guardian_contact' => $validated['guardianContact'],
            'guardian_address' => $validated['guardianAddress'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
        ];

        $student = Student::create($payload);

        return response()->json($student, 201);
    }

    public function show($id)
    {
        return response()->json(Student::findOrFail($id));
    }



    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
    
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'studentNo' => 'required|string|unique:students,student_no,' . $id,
            'section' => 'required|string',
            'program' => 'required|string',
            'year' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'email' => 'required|email|unique:students,email,' . $id,
            'contact' => 'required|string',
            'address' => 'required|string',
            'guardianName' => 'required|string',
            'guardianContact' => 'required|string',
            'guardianAddress' => 'required|string',
            'username' => 'required|string|unique:students,username,' . $id,
            'password' => 'nullable|string|min:8',
        ]);
    
        $payload = [
            'fullname' => $validated['fullname'],
            'student_no' => $validated['studentNo'],
            'section' => $validated['section'],
            'program' => $validated['program'],
            'year' => $validated['year'],
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'contact' => $validated['contact'],
            'address' => $validated['address'],
            'guardian_name' => $validated['guardianName'],
            'guardian_contact' => $validated['guardianContact'],
            'guardian_address' => $validated['guardianAddress'],
            'username' => $validated['username'],
        ];
    
        // Update lang kapag may laman yung password
        if ($request->filled('password')) {
            $payload['password'] = bcrypt($validated['password']);
        }
    
        $student->update($payload);
    
        return response()->json([
            'message' => 'Student updated successfully!',
            'data' => $student
        ]);
    }
    
    

    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids');

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'No student IDs provided'], 400);
        }

        Student::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Students deleted successfully']);
    }
}
