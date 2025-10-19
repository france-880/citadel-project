<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacultyLoad;
use App\Models\User;
use App\Models\AcademicManagement\Subject;

class FacultyLoadController extends Controller
{
    // Get faculty loads for a specific faculty
    public function getFacultyLoads($facultyId, Request $request)
    {
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');

        $query = FacultyLoad::with(['faculty', 'subject'])
            ->where('faculty_id', $facultyId);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        if ($semester) {
            $query->where('semester', $semester);
        }

        $facultyLoads = $query->get();

        return response()->json($facultyLoads);
    }

    // Store a new faculty load
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'subject_id' => 'nullable|exists:subjects,id', // Make subject_id nullable for manual entries
            'subject_code' => 'required|string|max:20',
            'subject_description' => 'required|string|max:255',
            'lec_hours' => 'required|integer|min:0',
            'lab_hours' => 'required|integer|min:0',
            'units' => 'required|integer|min:0',
            'section' => 'nullable|string|max:50',
            'schedule' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'type' => 'required|in:Full-time,Part-time',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|string|max:20'
        ]);

        // Check if this subject is already assigned to this faculty for the same academic year/semester
        $existingLoad = FacultyLoad::where('faculty_id', $validated['faculty_id'])
            ->where('subject_code', $validated['subject_code']) // Check by subject code instead of subject_id
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', $validated['semester'])
            ->first();

        if ($existingLoad) {
            return response()->json([
                'message' => 'This subject is already assigned to this faculty for the selected academic period.'
            ], 422);
        }

        $facultyLoad = FacultyLoad::create($validated);

        return response()->json([
            'message' => 'Faculty load created successfully',
            'data' => $facultyLoad->load(['faculty', 'subject'])
        ], 201);
    }

    // Update a faculty load
    public function update(Request $request, $id)
    {
        $facultyLoad = FacultyLoad::find($id);

        if (!$facultyLoad) {
            return response()->json(['message' => 'Faculty load not found'], 404);
        }

        $validated = $request->validate([
            'subject_code' => 'sometimes|required|string|max:20',
            'subject_description' => 'sometimes|required|string|max:255',
            'lec_hours' => 'sometimes|required|integer|min:0',
            'lab_hours' => 'sometimes|required|integer|min:0',
            'units' => 'sometimes|required|integer|min:0',
            'section' => 'nullable|string|max:50',
            'schedule' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'type' => 'sometimes|required|in:Full-time,Part-time',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|string|max:20'
        ]);

        $facultyLoad->update($validated);

        return response()->json([
            'message' => 'Faculty load updated successfully',
            'data' => $facultyLoad->load(['faculty', 'subject'])
        ]);
    }

    // Delete a faculty load
    public function destroy($id)
    {
        $facultyLoad = FacultyLoad::find($id);

        if (!$facultyLoad) {
            return response()->json(['message' => 'Faculty load not found'], 404);
        }

        $facultyLoad->delete();

        return response()->json(['message' => 'Faculty load deleted successfully']);
    }

    // Get all faculty loads with pagination
    public function index(Request $request)
    {
        $query = FacultyLoad::with(['faculty', 'subject']);

        // Filter by faculty
        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        // Filter by academic year
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by semester
        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }

        $facultyLoads = $query->paginate(15);

        return response()->json($facultyLoads);
    }
}
