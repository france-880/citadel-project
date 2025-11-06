<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentControllerAPI extends Controller
{
    // GET /students-with-logs
    public function getWithLogs()
    {
        $students = DB::table('students as s')
            ->leftJoin('entry_logs as e', function($join) {
                $join->on('s.student_no', '=', 'e.student_no');
            })
            ->select(
                's.id',
                's.student_no',
                's.fullname',
                's.program_id',
                's.year_section_id',
                DB::raw('MAX(e.timestamps) as latest_timestamp')
            )
            ->groupBy('s.id', 's.student_no', 's.fullname', 's.program_id', 's.year_section_id')
            ->orderBy('s.student_no')
            ->get();

        return response()->json($students);
    }

}
