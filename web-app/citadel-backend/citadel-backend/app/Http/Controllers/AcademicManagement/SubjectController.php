<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::all(); // Remove the with('program') relationship for now
        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'required|string|max:10|unique:subjects,subject_code',
            'subject_type' => 'required|in:Major,Minor,General Education,Elective',
            'days' => 'nullable|array',
            'days.*' => 'in:Mon,Tue,Wed,Thu,Fri,Sat',
            'lab_hours' => 'nullable|integer|min:0',
            'lec_hours' => 'nullable|integer|min:0',
            'units' => 'nullable|integer|min:0',
            'time' => 'nullable|string|max:20',
        ]);
        
        $subject = Subject::create($validated);
        return response()->json($subject, 201);
    }

    public function show($id)
    {
        $subject = Subject::find($id); // Remove the with('program') relationship for now
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }
        
        $validated = $request->validate([
            'subject_name' => 'sometimes|required|string|max:255',
            'subject_code' => 'sometimes|required|string|max:10|unique:subjects,subject_code,' . $id,
            'subject_type' => 'sometimes|required|in:Major,Minor,General Education,Elective',
            'days' => 'nullable|array',
            'days.*' => 'in:Mon,Tue,Wed,Thu,Fri,Sat',
            'lab_hours' => 'nullable|integer|min:0',
            'lec_hours' => 'nullable|integer|min:0',
            'units' => 'nullable|integer|min:0',
            'time' => 'nullable|string|max:20',
        ]);
        
        $subject->update($validated);
        return response()->json($subject);
    }
    
    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }
        $subject->delete();
        return response()->json(['message' => 'Subject deleted successfully'], 200);
    }   
}
