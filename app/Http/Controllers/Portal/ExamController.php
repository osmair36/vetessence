<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $tutor = auth('tutor')->user() ?? auth()->user()?->tutor;
        $pets = $tutor ? $tutor->pets()->with('exams')->get() : collect();
        $exams = $pets->flatMap->exams->sortByDesc('date');
        return view('portal.exams.index', compact('exams'));
    }
}
