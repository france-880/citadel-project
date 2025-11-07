<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use App\Models\Account;
use App\Models\AcademicManagement\Program;
use App\Models\AcademicManagement\College;
use App\Models\SectionOffering;
use App\Models\FacultyLoad;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for super admin
     */
    public function getStatistics(Request $request)
    {
        try {
            // Total Users
            $totalStudents = Student::count();
            $totalUsers = User::count();
            $totalAccounts = Account::count();
            
            // Count users by role
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();
            
            $coordinators = $usersByRole['program_head'] ?? 0;
            $faculty = ($usersByRole['faculty'] ?? 0) + ($usersByRole['professor'] ?? 0);
            $deans = $usersByRole['dean'] ?? 0;
            $registrars = $usersByRole['registrar'] ?? 0;
            
            // Total Programs
            $totalPrograms = Program::count();
            
            // Program Performance (students per program)
            $programsWithStudents = Program::withCount('students')
                ->orderBy('students_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($program) {
                    return [
                        'name' => $program->program_code ?? $program->program_name,
                        'students' => $program->students_count,
                        'attendance' => 0 // Placeholder - would need attendance system
                    ];
                });
            
            // Section Offerings Statistics
            $currentYear = $request->get('academic_year', date('Y'));
            $currentSemester = $request->get('semester', 'First');
            
            $totalSectionOfferings = SectionOffering::where('academic_year', $currentYear)
                ->where('semester', $currentSemester)
                ->count();
            
            $totalFacultyLoads = FacultyLoad::where('academic_year', $currentYear)
                ->where('semester', $currentSemester)
                ->count();
            
            // System Stats (simplified - would need session tracking)
            $activeUsers = User::where('updated_at', '>=', Carbon::now()->subHours(24))->count();
            
            // Recent Activities (would need activity log table)
            $recentActivities = $this->getRecentActivities();
            
            // Weekly Stats (placeholder - would need attendance data)
            $weeklyStats = $this->getWeeklyStats();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'totalUsers' => [
                        'students' => $totalStudents,
                        'coordinators' => $coordinators,
                        'faculty' => $faculty,
                        'deans' => $deans,
                        'registrars' => $registrars,
                        'total' => $totalStudents + $totalUsers + $totalAccounts
                    ],
                    'totalPrograms' => $totalPrograms,
                    'todayAttendance' => [
                        'total' => 0, // Placeholder
                        'present' => 0, // Placeholder
                        'absent' => 0, // Placeholder
                        'percentage' => 0 // Placeholder
                    ],
                    'systemStats' => [
                        'activeUsers' => $activeUsers,
                        'totalSessions' => 0, // Placeholder - would need session tracking
                        'averageSessionTime' => '0h 0m' // Placeholder
                    ],
                    'programs' => $programsWithStudents,
                    'sectionOfferings' => [
                        'total' => $totalSectionOfferings,
                        'facultyLoads' => $totalFacultyLoads
                    ],
                    'recentActivities' => $recentActivities,
                    'weeklyStats' => $weeklyStats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get recent activities (placeholder - would need activity log)
     */
    private function getRecentActivities()
    {
        // Placeholder - would need an activity_log table
        // For now, return sample activities based on recent database changes
        
        $activities = [];
        
        // Recent student registrations
        $recentStudents = Student::orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentStudents as $student) {
            $activities[] = [
                'id' => $student->id,
                'type' => 'registration',
                'message' => "New student registered: {$student->fullname}",
                'created_at' => $student->created_at->toISOString(),
                'icon' => 'UserCheck',
                'color' => 'text-blue-500'
            ];
        }
        
        // Recent faculty loads
        $recentFacultyLoads = FacultyLoad::orderBy('created_at', 'desc')
            ->with('faculty')
            ->limit(2)
            ->get();
        
        foreach ($recentFacultyLoads as $load) {
            if ($load->faculty) {
                $activities[] = [
                    'id' => $load->id,
                    'type' => 'faculty_load',
                    'message' => "Faculty load assigned: {$load->faculty->fullname} - {$load->subject_code}",
                    'created_at' => $load->created_at->toISOString(),
                    'icon' => 'BookOpen',
                    'color' => 'text-green-500'
                ];
            }
        }
        
        // Sort by created_at and return top 5
        return collect($activities)
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->all();
    }
    
    /**
     * Get weekly statistics (placeholder - would need attendance data)
     */
    private function getWeeklyStats()
    {
        // Placeholder - would need attendance tracking system
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $stats = [];
        
        foreach ($days as $day) {
            $stats[$day] = rand(85, 98); // Placeholder random values
        }
        
        return $stats;
    }
    
    /**
     * Get program statistics with more details
     */
    public function getProgramStatistics(Request $request)
    {
        try {
            $programs = Program::withCount('students')
                ->with('college')
                ->orderBy('students_count', 'desc')
                ->get()
                ->map(function ($program) {
                    return [
                        'id' => $program->id,
                        'name' => $program->program_name,
                        'code' => $program->program_code,
                        'students' => $program->students_count,
                        'college' => $program->college->college_name ?? 'N/A'
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $programs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch program statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for Dean
     */
    public function getDeanStatistics(Request $request)
    {
        try {
            // Get authenticated user (dean)
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            // Check if user is a dean (can be from Account model)
            $isDean = false;
            if ($user instanceof Account) {
                $isDean = $user->role === 'dean';
            } elseif ($user instanceof User) {
                $isDean = $user->role === 'dean';
            }
            
            if (!$isDean) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Dean access required.'
                ], 403);
            }

            // Get the college where this dean is assigned
            $college = College::where('dean_id', $user->id)->first();
            
            if (!$college) {
                return response()->json([
                    'success' => false,
                    'message' => 'No college assigned to this dean.'
                ], 404);
            }

            // Get all programs under this college
            $programIds = Program::where('college_id', $college->id)->pluck('id');

            // Get all students under these programs
            // If no programs, return empty result
            if ($programIds->isEmpty()) {
                $allStudents = collect([]);
            } else {
                $allStudents = Student::whereIn('program_id', $programIds)->get();
            }
            
            // Total students registered
            $totalStudents = $allStudents->count();
            
            // Regular students (students with year_section_id - assumed to be regular)
            $regularStudents = $allStudents->whereNotNull('year_section_id')->count();
            
            // Irregular students (students without year_section_id)
            $irregularStudents = $allStudents->whereNull('year_section_id')->count();

            // Get today's date for attendance (placeholder - no attendance system yet)
            $today = Carbon::today();
            
            // For now, we'll use placeholder attendance data
            // When attendance system is implemented, query actual attendance records
            $totalStudentsForAttendance = $totalStudents;
            $presentStudents = (int) ($totalStudentsForAttendance * 0.95); // 95% placeholder
            $absentStudents = $totalStudentsForAttendance - $presentStudents;
            $lastUpdated = Carbon::now()->format('g:i A');

            // Get recent activities for this college
            $recentActivities = $this->getDeanRecentActivities($programIds);

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'totalStudents' => $totalStudents,
                        'regularStudents' => $regularStudents,
                        'irregularStudents' => $irregularStudents,
                    ],
                    'attendanceOverview' => [
                        'present' => $presentStudents,
                        'absent' => $absentStudents,
                        'total' => $totalStudentsForAttendance,
                        'lastUpdated' => $lastUpdated,
                    ],
                    'recentActivities' => $recentActivities,
                    'college' => [
                        'id' => $college->id,
                        'name' => $college->college_name,
                        'code' => $college->college_code,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dean dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities for Dean's college
     */
    private function getDeanRecentActivities($programIds)
    {
        $activities = [];
        
        // Recent student registrations under dean's programs
        if ($programIds->isNotEmpty()) {
            $recentStudents = Student::whereIn('program_id', $programIds)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        
            foreach ($recentStudents as $student) {
                $timeAgo = $this->getTimeAgo($student->created_at);
                $activities[] = [
                    'activity' => "New student registration approved: {$student->fullname}",
                    'time' => $timeAgo,
                    'created_at' => $student->created_at->toISOString(),
                ];
            }
        }
        
        // Recent faculty loads (if available)
        $recentFacultyLoads = FacultyLoad::orderBy('created_at', 'desc')
            ->with('faculty')
            ->limit(2)
            ->get();
        
        foreach ($recentFacultyLoads as $load) {
            if ($load->faculty) {
                $timeAgo = $this->getTimeAgo($load->created_at);
                $activities[] = [
                    'activity' => "Faculty attendance reviewed: {$load->faculty->fullname}",
                    'time' => $timeAgo,
                    'created_at' => $load->created_at->toISOString(),
                ];
            }
        }
        
        // Sort by created_at and return top 5
        return collect($activities)
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->map(function($activity) {
                return [
                    'activity' => $activity['activity'],
                    'time' => $activity['time']
                ];
            })
            ->all();
    }

    /**
     * Get human-readable time ago
     */
    private function getTimeAgo($datetime)
    {
        $diff = Carbon::parse($datetime)->diffInHours(Carbon::now());
        
        if ($diff < 1) {
            $minutes = Carbon::parse($datetime)->diffInMinutes(Carbon::now());
            if ($minutes < 1) {
                return 'Just now';
            }
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 24) {
            return $diff . ' hour' . ($diff > 1 ? 's' : '') . ' ago';
        } else {
            $days = Carbon::parse($datetime)->diffInDays(Carbon::now());
            if ($days == 1) {
                return 'Yesterday';
            }
            return $days . ' days ago';
        }
    }
}
