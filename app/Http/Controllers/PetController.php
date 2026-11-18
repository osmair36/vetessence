<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:pets');
    }

    public function index(Request $request)
    {
        $query = Pet::with('tutors', 'breedRelation');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('breed', 'like', "%{$request->search}%");
            });
        }

        if ($request->species) {
            $query->where('species', $request->species);
        }

        $pets = $query->orderBy('name')->paginate(20);

        return view('pets.index', compact('pets'));
    }

    public function create()
    {
        $tutors = Tutor::with('user')->get()->sortBy(function($tutor) {
            return $tutor->user ? $tutor->user->name : '';
        });
        return view('pets.create', compact('tutors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'species' => 'required|in:' . implode(',', array_keys(config('species'))),
            'breed' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date',
            'weight' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:50',
            'microchip' => 'nullable|string|max:50',
            'size' => 'nullable|in:small,medium,large,giant',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'tutor_id' => 'required|exists:tutors,id',
            'is_primary' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $pet = Pet::create($validated);
            
            if ($request->hasFile('photo')) {
                $pet->savePhoto($request->file('photo'), 'pets');
            }
            
            $pet->tutors()->attach($validated['tutor_id'], [
                'is_primary' => $request->boolean('is_primary', true),
                'relationship' => 'proprietário',
            ]);

            DB::commit();
            return redirect()->route('pets.index')->with('success', 'Pet cadastrado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao cadastrar pet.')->withInput();
        }
    }

    public function show(Pet $pet)
    {
        $pet->load(['tutors', 'appointments', 'medicalRecords', 'vaccinations']);
        return view('pets.show', compact('pet'));
    }

    public function print(Pet $pet)
    {
        $pet->load([
            'tutors.user',
            'weightRecords' => fn($q) => $q->latest('measurement_date')->take(10),
            'vaccinations' => fn($q) => $q->latest('applied_at'),
            'parasiteControls' => fn($q) => $q->latest('application_date'),
            'medicalRecords.veterinarian',
            'exams' => fn($q) => $q->latest()->take(10),
            'surgeries' => fn($q) => $q->latest('surgery_date')->take(5),
            'hospitalizations' => fn($q) => $q->latest('admission_date')->take(5),
        ]);

        return view('pets.print', [
            'pet' => $pet,
            'title' => 'Ficha Cadastral e Clínica do Paciente',
            'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
            'pdfUrl' => route('pets.pdf', $pet),
        ]);
    }

    public function pdf(Pet $pet)
    {
        $pet->load([
            'tutors.user',
            'weightRecords' => fn($q) => $q->latest('measurement_date')->take(10),
            'vaccinations' => fn($q) => $q->latest('applied_at'),
            'parasiteControls' => fn($q) => $q->latest('application_date'),
            'medicalRecords.veterinarian',
            'exams' => fn($q) => $q->latest()->take(10),
            'surgeries' => fn($q) => $q->latest('surgery_date')->take(5),
            'hospitalizations' => fn($q) => $q->latest('admission_date')->take(5),
        ]);

        $filename = 'ficha-animal-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $pet->name)) . '-' . $pet->id;

        return \App\Services\DocumentPdfService::download('pets.print', [
            'pet' => $pet,
            'title' => 'Ficha Cadastral e Clínica do Paciente',
            'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
        ], $filename);
    }

    public function edit(Pet $pet)
    {
        $tutors = Tutor::with('user')->get()->sortBy(function($tutor) {
            return $tutor->user ? $tutor->user->name : '';
        });
        $petTutor = $pet->tutors()->first();
        return view('pets.edit', compact('pet', 'tutors', 'petTutor'));
    }

    public function update(Request $request, Pet $pet)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'species' => 'required|in:' . implode(',', array_keys(config('species'))),
            'breed' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date',
            'weight' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|max:50',
            'microchip' => 'nullable|string|max:50',
            'size' => 'nullable|in:small,medium,large,giant',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $pet->update($validated);

        if ($request->hasFile('photo')) {
            $pet->savePhoto($request->file('photo'), 'pets');
        }

        return redirect()->route('pets.index')->with('success', 'Pet atualizado com sucesso!');
    }

    public function destroy(Pet $pet)
    {
        if ($pet->appointments()->exists()) {
            return back()->with('error', 'Não é possível excluir o pet pois possui agendamentos/atendimentos vinculados.');
        }

        if ($pet->medicalRecords()->exists()) {
            return back()->with('error', 'Não é possível excluir o pet pois possui prontuários/histórico clínico vinculado.');
        }

        if ($pet->vaccinations()->exists()) {
            return back()->with('error', 'Não é possível excluir o pet pois possui histórico de vacinações.');
        }

        try {
            DB::transaction(function () use ($pet) {
                $pet->tutors()->detach();
                $pet->delete();
            });

            return redirect()->route('pets.index')->with('success', 'Pet excluído com sucesso!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Não foi possível excluir o pet: ' . $e->getMessage());
        }
    }
}
