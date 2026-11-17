<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index(Request $request)
    {
        $tutor = auth('tutor')->user() ?? auth()->user()?->tutor;
        $pets = $tutor ? $tutor->pets()->with('medicalRecords.vet')->get() : collect();
        $records = $pets->flatMap->medicalRecords->sortByDesc('created_at');
        return view('portal.medical-records.index', compact('records'));
    }

    public function show($id)
    {
        $tutor = auth('tutor')->user() ?? auth()->user()?->tutor;
        abort_unless($tutor, 403);
        $pet = $tutor->pets()->with('medicalRecords.vet')->findOrFail($id);
        return view('portal.medical-records.show', compact('pet'));
    }
}
