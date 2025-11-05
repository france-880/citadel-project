<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\Program;
use App\Models\AcademicManagement\College;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'subject_id' => 'required|exists:subjects,id'
        ]);

        // Check if subject is already assigned
        if ($program->subjects()->where('subject_id', $request->subject_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Subject is already assigned to this program'
            ], 400);
        }

        // Assign the subject
        $program->subjects()->attach($request->subject_id);

        return response()->json([
            'success' => true,
            'message' => 'Subject assigned successfully',
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
     */
    public function getSubjects($programId)
    {
        $program = Program::with('subjects')->find($programId);

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Program not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $program->subjects
        ]);
    }
}