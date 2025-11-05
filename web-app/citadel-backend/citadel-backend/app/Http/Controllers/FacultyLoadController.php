<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacultyLoad;
use App\Models\SectionOffering;
use App\Models\User;
use App\Models\AcademicManagement\Subject;

class FacultyLoadController extends Controller
{
    // Get faculty loads for a specific faculty
    public function getFacultyLoads($facultyId, Request $request)
    {
        $academicYear = $request->get('academic_year');
        $semester = $request->get('semester');

        $query = FacultyLoad::with(['faculty', 'subject', 'sectionOffering.schedules', 'sectionOffering.subject', 'sectionOffering.program'])
            ->where('faculty_id', $facultyId);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        if ($semester) {
            $query->where('semester', $semester);
        }

        $facultyLoads = $query->get()->map(function ($load) {
            $enriched = $this->enrichFacultyLoadData($load);
            
            // Convert to array and ensure formatted_section is included
            $array = $enriched->toArray();
            
            // Explicitly add formatted_section if it exists
            if (isset($enriched->formatted_section)) {
                $array['formatted_section'] = $enriched->formatted_section;
            }
            
            // Also ensure sectionOffering data is included if needed
            if ($enriched->sectionOffering) {
                $array['sectionOffering'] = $enriched->sectionOffering->toArray();
            }
            
            return $array;
        });

        return response()->json($facultyLoads);
    }

    // Store a new faculty load
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:users,id',
            'section_offering_id' => 'nullable|exists:section_offerings,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'subject_code' => 'nullable|string|max:20',
            'subject_description' => 'nullable|string|max:255',
            'lec_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'units' => 'nullable|integer|min:0',
            'section' => 'nullable|string|max:50',
            'schedule' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'type' => 'required|in:Full-time,Part-time',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|string|max:20'
        ]);

        // If section_offering_id is provided, derive data from it
        if (!empty($validated['section_offering_id'])) {
            $sectionOffering = SectionOffering::with(['subject', 'schedules'])->find($validated['section_offering_id']);
            
            if (!$sectionOffering) {
                return response()->json([
                    'message' => 'Section offering not found'
                ], 404);
            }

            // Derive data from section offering
            $validated['subject_id'] = $sectionOffering->subject_id;
            $validated['subject_code'] = $sectionOffering->subject->subject_code ?? $validated['subject_code'];
            $validated['subject_description'] = $sectionOffering->subject->subject_name ?? $validated['subject_description'];
            $validated['lec_hours'] = $sectionOffering->lec_hours ?? $sectionOffering->subject->lec_hours ?? $validated['lec_hours'] ?? 0;
            $validated['lab_hours'] = $sectionOffering->lab_hours ?? $sectionOffering->subject->lab_hours ?? $validated['lab_hours'] ?? 0;
            $validated['units'] = $sectionOffering->subject->units ?? $validated['units'] ?? 0;
            $validated['section'] = $sectionOffering->parent_section ?? $validated['section'];
            $validated['academic_year'] = $sectionOffering->academic_year ?? $validated['academic_year'];
            $validated['semester'] = $sectionOffering->semester ?? $validated['semester'];
            
            // Format schedule from schedules - always use all schedules from section offering
            if ($sectionOffering->schedules->isNotEmpty()) {
                $scheduleParts = $sectionOffering->schedules->map(function ($schedule) {
                    $day = strtoupper($schedule->day);
                    $startTime = date('g:i A', strtotime($schedule->start_time));
                    $endTime = date('g:i A', strtotime($schedule->end_time));
                    $room = $schedule->room ? " ({$schedule->room})" : '';
                    return "{$day} {$startTime}-{$endTime}{$room}";
                });
                // Always use schedules from section offering (source of truth)
                $validated['schedule'] = $scheduleParts->join(', ');
            } else {
                // Only use provided schedule if no schedules in section offering
                $validated['schedule'] = $validated['schedule'] ?? null;
            }
        } else {
            // Manual entry - require essential fields
            if (empty($validated['subject_code'])) {
                return response()->json([
                    'message' => 'Subject code is required when not linking to a section offering'
                ], 422);
            }
            if (empty($validated['subject_description'])) {
                return response()->json([
                    'message' => 'Subject description is required when not linking to a section offering'
                ], 422);
            }
        }

        // Check if this subject is already assigned to this faculty for the same academic year/semester
        $query = FacultyLoad::where('faculty_id', $validated['faculty_id']);
        
        if (!empty($validated['section_offering_id'])) {
            $query->where('section_offering_id', $validated['section_offering_id']);
        } else {
            $query->where('subject_code', $validated['subject_code'])
                  ->where('academic_year', $validated['academic_year'])
                  ->where('semester', $validated['semester']);
        }
        
        $existingLoad = $query->first();

        if ($existingLoad) {
            return response()->json([
                'message' => 'This subject is already assigned to this faculty for the selected academic period.'
            ], 422);
        }

        $facultyLoad = FacultyLoad::create($validated);
        $facultyLoad->load(['faculty', 'subject', 'sectionOffering.schedules', 'sectionOffering.subject', 'sectionOffering.program']);

        return response()->json([
            'message' => 'Faculty load created successfully',
            'data' => $this->enrichFacultyLoadData($facultyLoad)
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
            'section_offering_id' => 'nullable|exists:section_offerings,id',
            'subject_code' => 'nullable|string|max:20',
            'subject_description' => 'nullable|string|max:255',
            'lec_hours' => 'nullable|integer|min:0',
            'lab_hours' => 'nullable|integer|min:0',
            'units' => 'nullable|integer|min:0',
            'section' => 'nullable|string|max:50',
            'schedule' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'type' => 'sometimes|required|in:Full-time,Part-time',
            'academic_year' => 'nullable|string|max:10',
            'semester' => 'nullable|string|max:20'
        ]);

        // If section_offering_id is provided, derive data from it
        if (!empty($validated['section_offering_id'])) {
            $sectionOffering = SectionOffering::with(['subject', 'schedules'])->find($validated['section_offering_id']);
            
            if ($sectionOffering) {
                // Derive data from section offering
                $validated['subject_id'] = $sectionOffering->subject_id;
                $validated['subject_code'] = $sectionOffering->subject->subject_code ?? $validated['subject_code'] ?? $facultyLoad->subject_code;
                $validated['subject_description'] = $sectionOffering->subject->subject_name ?? $validated['subject_description'] ?? $facultyLoad->subject_description;
                $validated['lec_hours'] = $sectionOffering->lec_hours ?? $sectionOffering->subject->lec_hours ?? $validated['lec_hours'] ?? $facultyLoad->lec_hours;
                $validated['lab_hours'] = $sectionOffering->lab_hours ?? $sectionOffering->subject->lab_hours ?? $validated['lab_hours'] ?? $facultyLoad->lab_hours;
                $validated['units'] = $sectionOffering->subject->units ?? $validated['units'] ?? $facultyLoad->units;
                $validated['section'] = $sectionOffering->parent_section ?? $validated['section'] ?? $facultyLoad->section;
                $validated['academic_year'] = $sectionOffering->academic_year ?? $validated['academic_year'] ?? $facultyLoad->academic_year;
                $validated['semester'] = $sectionOffering->semester ?? $validated['semester'] ?? $facultyLoad->semester;
                
                // Format schedule from schedules - always use all schedules from section offering
                if ($sectionOffering->schedules->isNotEmpty()) {
                    $scheduleParts = $sectionOffering->schedules->map(function ($schedule) {
                        $day = strtoupper($schedule->day);
                        $startTime = date('g:i A', strtotime($schedule->start_time));
                        $endTime = date('g:i A', strtotime($schedule->end_time));
                        $room = $schedule->room ? " ({$schedule->room})" : '';
                        return "{$day} {$startTime}-{$endTime}{$room}";
                    });
                    // Always use schedules from section offering (source of truth)
                    $validated['schedule'] = $scheduleParts->join(', ');
                } else {
                    // Only use provided schedule if no schedules in section offering
                    $validated['schedule'] = $validated['schedule'] ?? $facultyLoad->schedule;
                }
            }
        }

        $facultyLoad->update($validated);
        $facultyLoad->load(['faculty', 'subject', 'sectionOffering.schedules', 'sectionOffering.subject', 'sectionOffering.program']);

        return response()->json([
            'message' => 'Faculty load updated successfully',
            'data' => $this->enrichFacultyLoadData($facultyLoad)
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
        $query = FacultyLoad::with(['faculty', 'subject', 'sectionOffering.schedules', 'sectionOffering.subject', 'sectionOffering.program']);

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
        
        // Enrich paginated data
        $facultyLoads->getCollection()->transform(function ($load) {
            $enriched = $this->enrichFacultyLoadData($load);
            
            // Convert to array and ensure formatted_section is included
            $array = $enriched->toArray();
            
            // Explicitly add formatted_section if it exists
            if (isset($enriched->formatted_section)) {
                $array['formatted_section'] = $enriched->formatted_section;
            }
            
            // Also ensure sectionOffering data is included if needed
            if ($enriched->sectionOffering) {
                $array['sectionOffering'] = $enriched->sectionOffering->toArray();
            }
            
            // Add student count based on program and year&section
            $studentCount = $this->getStudentCountForFacultyLoad($enriched);
            $array['student_count'] = $studentCount;
            
            return $array;
        });

        return response()->json($facultyLoads);
    }

    // Get all unique sections for a specific faculty
    public function getFacultySections($facultyId, Request $request)
    {
        $academicYear = $request->get('academic_year', '2024');
        $semester = $request->get('semester', 'First');

        // Get all faculty loads for this faculty
        $facultyLoads = FacultyLoad::where('faculty_id', $facultyId)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->with(['sectionOffering.program'])
            ->get();

        // Enrich each load and extract unique formatted_sections
        $sections = collect();
        foreach ($facultyLoads as $load) {
            $enriched = $this->enrichFacultyLoadData($load);
            if (!empty($enriched->formatted_section)) {
                $sections->push($enriched->formatted_section);
            } else if (!empty($enriched->section)) {
                // Fallback to section if formatted_section is not available
                $sections->push($enriched->section);
            }
        }

        // Remove duplicates, sort, and return as array
        $uniqueSections = $sections->unique()->sort()->values()->map(function($section) {
            // Remove suffixes like "West", "North", etc. for consistency
            return preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $section);
        })->filter()->unique()->sort()->values();

        return response()->json($uniqueSections->all());
    }

    // Get all unique sections across all faculty loads
    public function getAllSections(Request $request)
    {
        $academicYear = $request->get('academic_year', '2024');
        $semester = $request->get('semester', 'First');

        $sections = FacultyLoad::where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->distinct()
            ->pluck('section')
            ->sort()
            ->values();

        return response()->json($sections);
    }

    /**
     * Enrich faculty load data with computed values from section offering
     * Falls back to stored values when section_offering_id is not set
     */
    private function enrichFacultyLoadData($facultyLoad)
    {
        // If linked to section offering, use data from there (but allow overrides)
        if ($facultyLoad->sectionOffering) {
            $sectionOffering = $facultyLoad->sectionOffering;
            
            // Use stored values if available, otherwise derive from section offering
            $facultyLoad->computed_subject_code = $facultyLoad->subject_code ?? $sectionOffering->subject->subject_code ?? null;
            $facultyLoad->computed_subject_description = $facultyLoad->subject_description ?? $sectionOffering->subject->subject_name ?? null;
            $facultyLoad->computed_lec_hours = $facultyLoad->lec_hours ?? $sectionOffering->lec_hours ?? $sectionOffering->subject->lec_hours ?? 0;
            $facultyLoad->computed_lab_hours = $facultyLoad->lab_hours ?? $sectionOffering->lab_hours ?? $sectionOffering->subject->lab_hours ?? 0;
            $facultyLoad->computed_units = $facultyLoad->units ?? $sectionOffering->subject->units ?? 0;
            $facultyLoad->computed_section = $facultyLoad->section ?? $sectionOffering->parent_section ?? null;
            $facultyLoad->computed_academic_year = $facultyLoad->academic_year ?? $sectionOffering->academic_year ?? null;
            $facultyLoad->computed_semester = $facultyLoad->semester ?? $sectionOffering->semester ?? null;
            
            // Format schedule from schedules if not manually set
            // Always use schedules from section offering if available (they're the source of truth)
            if ($sectionOffering->schedules->isNotEmpty()) {
                $scheduleParts = $sectionOffering->schedules->map(function ($schedule) {
                    $day = strtoupper($schedule->day);
                    $startTime = date('g:i A', strtotime($schedule->start_time));
                    $endTime = date('g:i A', strtotime($schedule->end_time));
                    $room = $schedule->room ? " ({$schedule->room})" : '';
                    return "{$day} {$startTime}-{$endTime}{$room}";
                });
                $facultyLoad->computed_schedule = $scheduleParts->join(', ');
            } else {
                // Fallback to stored schedule if no schedules in section offering
                $facultyLoad->computed_schedule = $facultyLoad->schedule ?? '';
            }
            
            // Also extract room from schedules if available
            if (empty($facultyLoad->room) && $sectionOffering->schedules->isNotEmpty()) {
                $rooms = $sectionOffering->schedules->pluck('room')->filter()->unique()->values();
                if ($rooms->isNotEmpty()) {
                    $facultyLoad->computed_room = $rooms->join(', ');
                } else {
                    $facultyLoad->computed_room = $facultyLoad->room ?? 'TBA';
                }
            } else {
                $facultyLoad->computed_room = $facultyLoad->room ?? 'TBA';
            }
            
            // Format section title as "BSIT 1A" (Program + Year Level + Section)
            // Make sure program is loaded
            if (!$sectionOffering->relationLoaded('program')) {
                $sectionOffering->load('program');
            }
            
            // Get program name - check if program exists
            $programName = '';
            if ($sectionOffering->program) {
                $programName = $sectionOffering->program->program_code ?? $sectionOffering->program->program_name ?? '';
            }
            
            $yearLevel = $sectionOffering->year_level ?? '';
            $section = $sectionOffering->parent_section ?? '';  
            
            // Convert year level to number format (e.g., "First Year" -> "1", "4" -> "4")
            $yearLevelNum = '';
            if ($yearLevel) {
                // Check if it's already a number
                if (preg_match('/^\d+$/', trim($yearLevel))) {
                    $yearLevelNum = trim($yearLevel);
                } else {
                    // Convert text to number (e.g., "First Year" -> "1")
                    $yearMap = [
                        'first year' => '1', 'first' => '1', '1st year' => '1', '1st' => '1',
                        'second year' => '2', 'second' => '2', '2nd year' => '2', '2nd' => '2',
                        'third year' => '3', 'third' => '3', '3rd year' => '3', '3rd' => '3',
                        'fourth year' => '4', 'fourth' => '4', '4th year' => '4', '4th' => '4',
                        'fifth year' => '5', 'fifth' => '5', '5th year' => '5', '5th' => '5'
                    ];
                    $yearLower = strtolower(trim($yearLevel));
                    $yearLevelNum = $yearMap[$yearLower] ?? $yearLevel;
                }
            }
            
            // Build formatted title: "BSIT 1A" (remove suffixes like "West", "North", etc.)
            if ($programName && $yearLevelNum && $section) {
                // Remove common suffixes from section (West, North, East, South, etc.)
                $cleanSection = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $section);
                $facultyLoad->formatted_section = strtoupper($programName . ' ' . $yearLevelNum . $cleanSection);
            } else if ($programName && $section) {
                // Fallback if year level not available - also remove suffixes
                $cleanSection = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $section);
                $facultyLoad->formatted_section = strtoupper($programName . ' ' . $cleanSection);
            } else {
                // Last fallback to computed_section - also remove suffixes
                $fallbackSection = $facultyLoad->computed_section ?? $facultyLoad->section ?? '';
                $facultyLoad->formatted_section = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $fallbackSection);
            }
            
            // Log for debugging
            \Log::debug('Formatted section', [
                'faculty_load_id' => $facultyLoad->id,
                'program_name' => $programName,
                'program_code' => $sectionOffering->program->program_code ?? 'N/A',
                'program_id' => $sectionOffering->program_id ?? 'N/A',
                'year_level' => $yearLevel,
                'year_level_num' => $yearLevelNum,
                'section' => $section,
                'formatted_section' => $facultyLoad->formatted_section ?? 'NOT SET',
                'has_program' => $sectionOffering->program ? 'yes' : 'no',
                'program_exists' => isset($sectionOffering->program) ? 'yes' : 'no'
            ]);
            
            // Force set formatted_section even if empty to ensure it's always present
            // Also remove suffixes like "West", "North", etc.
            if (empty($facultyLoad->formatted_section)) {
                $fallback = $facultyLoad->computed_section ?? $facultyLoad->section ?? 'N/A';
                $facultyLoad->formatted_section = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $fallback);
            } else {
                // Remove suffixes from already formatted section
                $facultyLoad->formatted_section = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $facultyLoad->formatted_section);
            }
        } else {
            // Manual entry - use stored values
            $facultyLoad->computed_subject_code = $facultyLoad->subject_code;
            $facultyLoad->computed_subject_description = $facultyLoad->subject_description;
            $facultyLoad->computed_lec_hours = $facultyLoad->lec_hours ?? 0;
            $facultyLoad->computed_lab_hours = $facultyLoad->lab_hours ?? 0;
            $facultyLoad->computed_units = $facultyLoad->units ?? 0;
            $facultyLoad->computed_section = $facultyLoad->section;
            $facultyLoad->computed_academic_year = $facultyLoad->academic_year;
            $facultyLoad->computed_semester = $facultyLoad->semester;
            $facultyLoad->computed_schedule = $facultyLoad->schedule ?? '';
            $facultyLoad->computed_room = $facultyLoad->room ?? 'TBA';
            
            // For manual entries, try to format from section if possible
            // Parse section string like "BSIT-4A" or "4A"
            $sectionStr = $facultyLoad->section ?? '';
            if ($sectionStr) {
                // Remove suffixes first (West, North, etc.)
                $sectionStr = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $sectionStr);
                
                // Try to extract program, year, and section from string
                if (preg_match('/([A-Z]+)[\s\-]*(\d+)([A-Z]+)/i', $sectionStr, $matches)) {
                    $facultyLoad->formatted_section = strtoupper($matches[1] . ' ' . $matches[2] . $matches[3]);
                } else if (preg_match('/(\d+)([A-Z]+)/i', $sectionStr, $matches)) {
                    // Just year and section, no program
                    $facultyLoad->formatted_section = strtoupper($matches[1] . $matches[2]);
                } else {
                    $facultyLoad->formatted_section = $sectionStr;
                }
            } else {
                $facultyLoad->formatted_section = $facultyLoad->section ?? '';
            }
            
            // Always remove suffixes from formatted_section in manual entries too
            if (!empty($facultyLoad->formatted_section)) {
                $facultyLoad->formatted_section = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $facultyLoad->formatted_section);
            }
        }
        
        return $facultyLoad;
    }
    
    /**
     * Get student count for a faculty load based on program and year&section
     */
    private function getStudentCountForFacultyLoad($facultyLoad)
    {
        try {
            $programId = null;
            $yearLevel = null;
            $section = null;
            
            // Get program_id, year_level, and section from sectionOffering
            if ($facultyLoad->sectionOffering) {
                $so = $facultyLoad->sectionOffering;
                $programId = $so->program_id;
                $yearLevel = $so->year_level;
                $section = $so->parent_section;
                
                // Remove suffixes from section (e.g., "C-WEST" -> "C")
                if ($section) {
                    $section = preg_replace('/\s*-\s*(West|North|East|South|WEST|NORTH|EAST|SOUTH)$/i', '', $section);
                }
                
                // Convert year level to number if it's text
                if ($yearLevel && !preg_match('/^\d+$/', trim($yearLevel))) {
                    $yearMap = [
                        'first year' => '1', 'first' => '1', '1st year' => '1', '1st' => '1',
                        'second year' => '2', 'second' => '2', '2nd year' => '2', '2nd' => '2',
                        'third year' => '3', 'third' => '3', '3rd year' => '3', '3rd' => '3',
                        'fourth year' => '4', 'fourth' => '4', '4th year' => '4', '4th' => '4',
                        'fifth year' => '5', 'fifth' => '5', '5th year' => '5', '5th' => '5'
                    ];
                    $yearLower = strtolower(trim($yearLevel));
                    $yearLevel = $yearMap[$yearLower] ?? $yearLevel;
                }
            }
            
            // If we have the required data, count students
            if ($programId && $yearLevel && $section) {
                $count = \App\Models\Student::where('program_id', $programId)
                    ->whereHas('yearSection', function($q) use ($yearLevel, $section) {
                        // Handle both numeric and text year levels
                        $yearLevelNum = is_numeric($yearLevel) ? $yearLevel : $yearLevel;
                        if (!is_numeric($yearLevel)) {
                            $yearMap = [
                                'first year' => '1', 'first' => '1', '1st year' => '1', '1st' => '1',
                                'second year' => '2', 'second' => '2', '2nd year' => '2', '2nd' => '2',
                                'third year' => '3', 'third' => '3', '3rd year' => '3', '3rd' => '3',
                                'fourth year' => '4', 'fourth' => '4', '4th year' => '4', '4th' => '4',
                                'fifth year' => '5', 'fifth' => '5', '5th year' => '5', '5th' => '5'
                            ];
                            $yearLower = strtolower(trim($yearLevel));
                            $yearLevelNum = $yearMap[$yearLower] ?? $yearLevel;
                        }
                        
                        $q->where(function($subQ) use ($yearLevelNum, $yearLevel) {
                            $subQ->where('year_level', $yearLevelNum)
                                 ->orWhere('year_level', $yearLevel)
                                 ->orWhere('year_level', 'like', '%' . $yearLevelNum . '%');
                        });
                        
                        // Remove suffixes for matching
                        $cleanSection = preg_replace('/\s*-\s*(West|North|East|South)$/i', '', $section);
                        $q->where(function($subQ) use ($section, $cleanSection) {
                            $subQ->where('section', $section)
                                 ->orWhere('section', $cleanSection)
                                 ->orWhere('section', 'like', $cleanSection . '%');
                        });
                    })
                    ->count();
                
                return $count;
            }
        } catch (\Exception $e) {
            \Log::error('Error counting students for faculty load: ' . $e->getMessage());
        }
        
        return 0;
    }
}
