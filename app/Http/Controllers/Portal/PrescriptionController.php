<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index()
    {
        $tutor = auth('tutor')->user() ?? auth()->user()?->tutor;
        $pets = $tutor ? $tutor->pets()->with('medicalRecords.prescriptions')->get() : collect();
        $prescriptions = $pets->flatMap->medicalRecords->flatMap->prescriptions->sortByDesc('created_at');
        return view('portal.prescriptions.index', compact('prescriptions'));
    }
}
