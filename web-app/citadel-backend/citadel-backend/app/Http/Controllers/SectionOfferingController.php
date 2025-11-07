<?php

namespace App\Http\Controllers;

use App\Models\SectionOffering;
use App\Models\SectionOfferingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SectionOfferingController extends Controller
{
    /**
     * Display a listing of section offerings.
     */
    public function index(Request $request)
    {
        $query = SectionOffering::with(['program', 'subject', 'schedules']);

        // Filter by program_id if provided
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by academic_year if provided
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by semester if provided
        if ($request->has('semester')) {
            $query->where('semester', $request->semester);
        }

        // Filter by year_level if provided
        if ($request->has('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        // Filter by parent_section if provided
        if ($request->has('parent_section')) {
            $query->where('parent_section', $request->parent_section);
        }

        // Filter by subject_id if provided
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Exclude already assigned section offerings if requested (for faculty loading)
        if ($request->has('exclude_assigned') && $request->exclude_assigned == 'true') {
            $query->whereDoesntHave('facultyLoads', function($q) use ($request) {
                // Optionally filter by academic_year and semester if provided
                if ($request->has('academic_year')) {
                    $q->where('academic_year', $request->academic_year);
                }
                if ($request->has('semester')) {
                    $q->where('semester', $request->semester);
                }
            });
        }

        $sectionOfferings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $sectionOfferings
        ]);
    }

    /**
     * Store a newly created section offering in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'academic_year' => 'required|string',
            'semester' => 'required|string',
            'year_level' => 'required|string',
            'parent_section' => 'required|string',
            'subject_id' => 'required|exists:subjects,id',
            'slots' => 'nullable|integer|min:0',
            'lec_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if section offering already exists
        $exists = SectionOffering::where([
            'program_id' => $request->program_id,
            'academic_year' => $request->academic_year,
            'semester' => $request->semester,
            'year_level' => $request->year_level,
            'parent_section' => $request->parent_section,
            'subject_id' => $request->subject_id,
        ])->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This subject is already offered in this section'
            ], 409);
        }

        $sectionOffering = SectionOffering::create($request->all());
        $sectionOffering->load(['program', 'subject', 'schedules']);

        return response()->json([
            'success' => true,
            'message' => 'Section offering created successfully',
            'data' => $sectionOffering
        ], 201);
    }

    /**
     * Display the specified section offering.
     */
    public function show($id)
    {
        $sectionOffering = SectionOffering::with(['program', 'subject', 'schedules'])->find($id);

        if (!$sectionOffering) {
            return response()->json([
                'success' => false,
                'message' => 'Section offering not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sectionOffering
        ]);
    }

    /**
     * Update the specified section offering in storage.
     */
    public function update(Request $request, $id)
    {
        $sectionOffering = SectionOffering::find($id);

        if (!$sectionOffering) {
            return response()->json([
                'success' => false,
                'message' => 'Section offering not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'program_id' => 'sometimes|required|exists:programs,id',
            'academic_year' => 'sometimes|required|string',
            'semester' => 'sometimes|required|string',
            'year_level' => 'sometimes|required|string',
            'parent_section' => 'sometimes|required|string',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'slots' => 'nullable|integer|min:0',
            'lec_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'schedules' => 'nullable|array',
            'schedules.*.day' => 'required|string',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update section offering basic info
            $sectionOffering->update($request->except('schedules'));

            // Handle schedules if provided
            if ($request->has('schedules')) {
                // Delete old schedules
                $sectionOffering->schedules()->delete();

                // Create new schedules
                foreach ($request->schedules as $schedule) {
                    $sectionOffering->schedules()->create($schedule);
                }
            }

            $sectionOffering->load(['program', 'subject', 'schedules']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Section offering updated successfully',
                'data' => $sectionOffering
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update section offering',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified section offering from storage.
     */
    public function destroy($id)
    {
        $sectionOffering = SectionOffering::find($id);

        if (!$sectionOffering) {
            return response()->json([
                'success' => false,
                'message' => 'Section offering not found'
            ], 404);
        }

        $sectionOffering->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section offering deleted successfully'
        ]);
    }
}
