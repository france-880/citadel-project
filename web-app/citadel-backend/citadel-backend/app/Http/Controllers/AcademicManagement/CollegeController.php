<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\College;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    public function index()
    {
        $colleges = College::with('dean')->get();
        return response()->json($colleges);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'college_name' => 'required|string|max:255',
            'college_code' => 'required|string|max:10|unique:colleges,college_code',
            'college_dean_id' => 'nullable|exists:accounts,id',
            'college_status' => 'required|in:Active,Inactive'
        ]);
        
        $college = College::create($validated);
        return response()->json($college, 201);
    }

    public function show($id)
    {
        $college = College::with('dean')->find($id);
        if (!$college) {
            return response()->json(['message' => 'College not found'], 404);
        }
        return response()->json($college);
    }
    public function update(Request $request, $id)
    {
        $college = College::find($id);
        if (!$college) {
            return response()->json(['message' => 'College not found'], 404);
        }
        
        $validated = $request->validate([
            'college_name' => 'sometimes|required|string|max:255',
            'college_code' => 'sometimes|required|string|max:10|unique:colleges,college_code,' . $id,
            'college_dean_id' => 'nullable|exists:accounts,id',
            'college_status' => 'sometimes|required|in:Active,Inactive'
        ]);
        
        $college->update($validated);
        return response()->json($college);
    }
    public function destroy($id)
    {
        $college = College::find($id);
        if (!$college) {
            return response()->json(['message' => 'College not found'], 404);
        }
        $college->delete();
        return response()->json(['message' => 'College deleted successfully'], 200);
    }
}
