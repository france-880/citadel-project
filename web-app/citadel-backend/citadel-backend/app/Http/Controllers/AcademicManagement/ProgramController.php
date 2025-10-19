<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $programs = Program::with('head')->get();
        return response()->json($programs);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|max:10|unique:programs,program_code',
            'program_head_id' => 'nullable|exists:accounts,id',
            'program_status' => 'required|in:Active,Inactive'
        ]);
        
        $program = Program::create($validated);
        return response()->json($program, 201);
    }
    public function show($id)
    {
        $program = Program::with('head')->find($id);
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        return response()->json($program);
    }
    public function update(Request $request, $id)
    {
        $program = Program::find($id);
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        
        $validated = $request->validate([
            'program_name' => 'sometimes|required|string|max:255',
            'program_code' => 'sometimes|required|string|max:10|unique:programs,program_code,' . $id,
            'program_head_id' => 'nullable|exists:accounts,id',
            'program_status' => 'sometimes|required|in:Active,Inactive'
        ]);
        
        $program->update($validated);
        return response()->json($program);
    }
    public function destroy($id)
    {
        $program = Program::find($id);
        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        $program->delete();
        return response()->json(['message' => 'Program deleted successfully'], 200);
    }
}
