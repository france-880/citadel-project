<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subjects = Subject::all();
        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'required|string|unique:subjects,subject_code|max:20',
            'units' => 'nullable|integer|min:1|max:10',
            'semester' => 'nullable|string|max:50',
            'year_level' => 'nullable|string|max:50'
        ]);

        $subject = Subject::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => $subject
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subject
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $request->validate([
            'subject_name' => 'sometimes|required|string|max:255',
            'subject_code' => 'sometimes|required|string|unique:subjects,subject_code,' . $id . '|max:20',
            'units' => 'nullable|integer|min:1|max:10',
            'semester' => 'nullable|string|max:50',
            'year_level' => 'nullable|string|max:50'
        ]);

        $subject->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => $subject
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        // Add any additional checks here (e.g., if subject is used in curriculum)
        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }

    /**
     * Get subjects by year level
     */
    public function getByYearLevel($yearLevel)
    {
        $subjects = Subject::where('year_level', $yearLevel)->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Get subjects by semester
     */
    public function getBySemester($semester)
    {
        $subjects = Subject::where('semester', $semester)->get();

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }
}