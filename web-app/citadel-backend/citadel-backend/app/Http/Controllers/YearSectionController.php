<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\YearSection;

class YearSectionController extends Controller
{
    // READ-ONLY
    public function index()
    {
        $yearSections = YearSection::orderBy('year_level')->orderBy('section')->get(['id', 'year_level', 'section']);

        return response()->json([
            'success' => true,
            'data' => $yearSections
        ]);
    }
}
