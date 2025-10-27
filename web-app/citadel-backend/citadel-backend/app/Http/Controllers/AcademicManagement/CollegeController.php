<?php

namespace App\Http\Controllers\AcademicManagement;

use App\Http\Controllers\Controller;
use App\Models\AcademicManagement\College;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollegeController extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $colleges = College::with('dean')->get();
        return response()->json([
            'success' => true,
            'data' => $colleges
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'college_name' => 'required|string|max:255',
            'college_code' => 'required|string|unique:colleges,college_code|max:10',
            'dean_id' => 'nullable|exists:accounts,id'
        ]);

        $college = College::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'College created successfully',
            'data' => $college
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $college = College::with(['dean', 'accounts', 'programs'])->find($id);

        if (!$college) {
            return response()->json([
                'success' => false,
                'message' => 'College not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $college
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $college = College::find($id);

        if (!$college) {
            return response()->json([
                'success' => false,
                'message' => 'College not found'
            ], 404);
        }

        $request->validate([
            'college_name' => 'sometimes|required|string|max:255',
            'college_code' => 'sometimes|required|string|unique:colleges,college_code,' . $id . '|max:10',
            'dean_id' => 'nullable|exists:accounts,id'
        ]);

        $college->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'College updated successfully',
            'data' => $college
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $college = College::find($id);

        if (!$college) {
            return response()->json([
                'success' => false,
                'message' => 'College not found'
            ], 404);
        }

        // Check if college has programs or accounts
        if ($college->programs()->count() > 0 || $college->accounts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete college with existing programs or accounts'
            ], 422);
        }

        $college->delete();

        return response()->json([
            'success' => true,
            'message' => 'College deleted successfully'
        ]);
    }

    /**
     * Get available deans for assignment
     */
    public function getAvailableDeans()
    {
        try {
            \Log::info('Fetching available deans...');
            
            // ✅ Check kung gumagana ang query
            $deans = Account::where('role', 'dean')
                ->whereDoesntHave('collegeAsDean')
                ->get(['id', 'fullname', 'email']);
                
            \Log::info('Found deans:', ['count' => $deans->count(), 'deans' => $deans->toArray()]);

            return response()->json([
                'success' => true,
                'data' => $deans
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching deans:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load deans: ' . $e->getMessage()
            ], 500);
        }
    }
}