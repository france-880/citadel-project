<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\AcademicManagement\Program;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get student reports (without attendance data since no attendance system yet)
     */
    public function getStudentReports(Request $request)
    {
        try {
            // Use leftJoin to include students without programs
            $query = Student::with('program');

            // Filter by program
            if ($request->has('program_id') && $request->program_id) {
                $query->where('program_id', $request->program_id);
            }

            // Filter by program code (if provided)
            if ($request->has('program_code') && $request->program_code && $request->program_code !== '') {
                // Try to match by program code (case insensitive)
                $programCode = $request->program_code;
                $query->whereHas('program', function($q) use ($programCode) {
                    $q->whereRaw('UPPER(program_code) = UPPER(?)', [$programCode])
                      ->orWhereRaw('UPPER(program_code) LIKE UPPER(?)', ['%' . $programCode . '%']);
                });
            }

            // Date filters (for future attendance data)
            // Currently not used since no attendance system
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');

            $students = $query->orderBy('fullname')->get();

            // Log for debugging
            \Log::info('ReportController: Found ' . $students->count() . ' students');

            // Transform to report format
            $reportRows = $students->map(function ($student) {
                return [
                    'student_id' => $student->student_no ?? 'N/A',
                    'name' => $student->fullname ?? 'N/A',
                    'program' => $student->program ? ($student->program->program_code ?? 'N/A') : 'N/A',
                    'program_name' => $student->program ? ($student->program->program_name ?? 'N/A') : 'N/A',
                    // No attendance data yet
                    // 'in_time' => null,
                    // 'out_time' => null,
                    // 'status' => null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $reportRows->values()->all(), // Ensure it's a proper array
                'count' => $reportRows->count(),
                'message' => 'Report generated successfully. Found ' . $reportRows->count() . ' student(s).'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }
}
