<?php

namespace App\Http\Controllers;

use App\Models\Vaccination;
use App\Models\Pet;
use App\Models\User;
use App\Models\Role;
use App\Events\ProcedurePerformed;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VaccinationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:vacinas');
    }

    public function index(Request $request)
    {
        $query = Vaccination::with(['pet', 'vet']);

        if ($request->pet_id) {
            $query->where('pet_id', $request->pet_id);
        }

        if ($request->search) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('vaccine', 'like', "%{$term}%")
                  ->orWhere('laboratory', 'like', "%{$term}%")
                  ->orWhere('manufacturer', 'like', "%{$term}%")
                  ->orWhere('batch', 'like', "%{$term}%")
                  ->orWhereHas('pet', fn($pq) => $pq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $vaccinations = $query
            ->orderByRaw('CASE WHEN date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('date', 'desc')
            ->orderBy('next_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('vaccinations.index', compact('vaccinations'));
    }

    public function create(Request $request)
    {
        $pets = Pet::where('is_active', true)->orderBy('name')->get();
        $veterinarians = $this->getVeterinarians();
        $completingVaccination = $request->complete_vaccination_id
            ? Vaccination::with('pet')->find($request->complete_vaccination_id)
            : null;
        $selectedPet = $completingVaccination?->pet
            ?? ($request->pet_id ? Pet::find($request->pet_id) : null);
        $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get();
        $laboratories = $this->getKnownLaboratories();

        return view('vaccinations.create', compact('pets', 'veterinarians', 'selectedPet', 'products', 'laboratories', 'completingVaccination'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pet_id' => 'required|exists:pets,id',
            'vaccine' => 'required|string|max:100',
            'date' => 'required|date',
            'next_date' => 'nullable|date|after:date',
            'next_date_manual' => 'nullable|boolean',
            'batch' => 'nullable|string|max:50',
            'lot' => 'nullable|string|max:50',
            'laboratory' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'dose' => 'nullable|string|max:50',
            'vet_id' => 'required|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string',
            'complete_vaccination_id' => 'nullable|exists:vaccinations,id',
        ]);

        $explicitPendingId = $validated['complete_vaccination_id'] ?? null;
        $wasManual = (bool) ($validated['next_date_manual'] ?? false);
        unset($validated['complete_vaccination_id'], $validated['next_date_manual']);

        // Cálculo confiável no backend: preserva alteração manual e preenche automaticamente se omitido
        $product = !empty($validated['product_id']) ? \App\Models\Product::find($validated['product_id']) : null;
        $pet = Pet::find($validated['pet_id']);
        $validated['next_date'] = app(\App\Services\ApplicationScheduleService::class)->resolveNextDateForPersistence(
            $validated['next_date'] ?? null,
            $wasManual,
            $validated['date'],
            $product,
            $pet,
            $validated['vaccine'],
            'vaccination',
            null,
            $validated['dose'] ?? null
        );

        $lab = $validated['laboratory'] ?? $validated['manufacturer'] ?? null;
        $validated['laboratory'] = $lab;
        $validated['manufacturer'] = $lab;

        if (!empty($validated['batch']) && empty($validated['lot'])) {
            $validated['lot'] = $validated['batch'];
        } elseif (!empty($validated['lot']) && empty($validated['batch'])) {
            $validated['batch'] = $validated['lot'];
        }

        $vaccination = Vaccination::create($validated);

        // Conclui automaticamente a pendencia anterior correspondente e cancela lembretes futuros
        app(\App\Services\Vaccination\VaccinationNotificationCenterService::class)
            ->autoCompletePreviousForNewVaccination($vaccination, $explicitPendingId ? (int) $explicitPendingId : null);

        event(new ProcedurePerformed($vaccination));

        return redirect()->route('vaccinations.index')->with('success', 'Vacina registrada e pendências anteriores atualizadas!');
    }

    public function show(Vaccination $vaccination)
    {
        $vaccination->load(['pet', 'vet']);
        return view('vaccinations.show', compact('vaccination'));
    }

    public function edit(Vaccination $vaccination)
    {
        $pets = Pet::where('is_active', true)->orderBy('name')->get();
        $veterinarians = $this->getVeterinarians();
        $products = \App\Models\Product::where('is_active', true)->orderBy('name')->get();
        $laboratories = $this->getKnownLaboratories();

        return view('vaccinations.edit', compact('vaccination', 'pets', 'veterinarians', 'products', 'laboratories'));
    }

    public function update(Request $request, Vaccination $vaccination)
    {
        $validated = $request->validate([
            'vaccine' => 'required|string|max:100',
            'date' => 'required|date',
            'next_date' => 'nullable|date|after_or_equal:date',
            'batch' => 'nullable|string|max:50',
            'lot' => 'nullable|string|max:50',
            'laboratory' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'dose' => 'nullable|string|max:50',
            'vet_id' => 'required|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string',
        ]);

        if (array_key_exists('laboratory', $validated) || array_key_exists('manufacturer', $validated)) {
            $lab = $validated['laboratory'] ?? $validated['manufacturer'] ?? null;
            $validated['laboratory'] = $lab;
            $validated['manufacturer'] = $lab;
        }

        if (array_key_exists('batch', $validated)) {
            $validated['lot'] = $validated['batch'];
        }

        $vaccination->update($validated);

        return redirect()->route('vaccinations.index')->with('success', 'Vacina atualizada!');
    }

    public function destroy(Vaccination $vaccination)
    {
        $vaccination->delete();
        return redirect()->route('vaccinations.index')->with('success', 'Vacina excluída!');
    }

    public function certificate(Pet $pet)
    {
        $vaccinations = $pet->vaccinations()
            ->with('vet')
            ->where('status', 'applied')
            ->whereNotNull('date')
            ->orderBy('date', 'desc')
            ->get();
        $pet->load('tutors');

        $pdf = Pdf::loadView('vaccinations.certificate-pdf', compact('pet', 'vaccinations'));

        return $pdf->download("certificado-vacinas-{$pet->name}.pdf");
    }

    public function cardPrint(Pet $pet)
    {
        $vaccinations = $pet->vaccinations()->with(['vet', 'veterinarian'])->orderBy('date', 'desc')->get();
        $pet->load('tutors');

        return view('vaccinations.card-print', [
            'pet' => $pet,
            'vaccinations' => $vaccinations,
            'title' => 'Carteira de Vacinação Veterinária',
            'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
            'pdfUrl' => route('pets.vaccinations.card.pdf', $pet),
        ]);
    }

    public function cardPdf(Pet $pet)
    {
        $vaccinations = $pet->vaccinations()->with(['vet', 'veterinarian'])->orderBy('date', 'desc')->get();
        $pet->load('tutors');

        $filename = 'carteira-vacinacao-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $pet->name)) . '-' . $pet->id;

        return \App\Services\DocumentPdfService::download('vaccinations.card-print', [
            'pet' => $pet,
            'vaccinations' => $vaccinations,
            'title' => 'Carteira de Vacinação Veterinária',
            'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
        ], $filename);
    }

    public function reportPrint(Request $request)
    {
        $query = Vaccination::with(['pet.tutors', 'vet']);

        if ($request->pet_id) {
            $query->where('pet_id', $request->pet_id);
        }
        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if (!$request->pet_id && !$request->date_from && !$request->date_to) {
            $query->limit(500);
        }

        $vaccinations = $query->orderBy('date', 'desc')->get();

        $subtitle = 'Relatório Geral de Vacinações';
        if ($request->date_from && $request->date_to) {
            $subtitle .= ' — Período: ' . Carbon::parse($request->date_from)->format('d/m/Y') . ' a ' . Carbon::parse($request->date_to)->format('d/m/Y');
        }

        return view('vaccinations.report-print', [
            'vaccinations' => $vaccinations,
            'title' => 'Relatório Geral de Vacinações',
            'periodSubtitle' => $subtitle,
            'pdfUrl' => route('vaccinations.report.pdf', $request->all()),
        ]);
    }

    public function reportPdf(Request $request)
    {
        $query = Vaccination::with(['pet.tutors', 'vet']);

        if ($request->pet_id) {
            $query->where('pet_id', $request->pet_id);
        }
        if ($request->date_from) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if (!$request->pet_id && !$request->date_from && !$request->date_to) {
            $query->limit(500);
        }

        $vaccinations = $query->orderBy('date', 'desc')->get();

        $subtitle = 'Relatório Geral de Vacinações';
        if ($request->date_from && $request->date_to) {
            $subtitle .= ' — Período: ' . Carbon::parse($request->date_from)->format('d/m/Y') . ' a ' . Carbon::parse($request->date_to)->format('d/m/Y');
        }

        return \App\Services\DocumentPdfService::download('vaccinations.report-print', [
            'vaccinations' => $vaccinations,
            'title' => 'Relatório Geral de Vacinações',
            'periodSubtitle' => $subtitle,
        ], 'relatorio-vacinacoes-' . now()->format('Y-m-d'), 'landscape');
    }

    public function forecast(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $species = $request->get('species');

        $query = \App\Models\Vaccination::with(['pet', 'vet'])
            ->openPending()
            ->whereBetween('next_date', [Carbon::today(), Carbon::today()->addDays($days)]);

        if ($species) {
            $query->whereHas('pet', fn($q) => $q->where('species', $species));
        }

        $vaccinations = $query->orderBy('next_date')->get();

        $speciesList = \App\Models\Pet::select('species')->distinct()->whereNotNull('species')->pluck('species');

        return view('vaccinations.forecast', compact('vaccinations', 'days', 'species', 'speciesList'));
    }

    protected function getVeterinarians()
    {
        return User::where('is_active', true)
            ->where(fn($q) => $q->whereHas('role', fn($q) => $q->where('slug', 'veterinario'))->orWhere('is_veterinarian', true))
            ->orderBy('name')
            ->get();
    }

    protected function getKnownLaboratories(): array
    {
        return [
            'ZOETIS',
            'VIRBAC',
            'PFIZER',
            'OURO FINO',
            'BAYER',
            'ELANCO',
            'MSD',
            'MERIAL',
            'CEVA',
            'VETNIL',
            'KONIG',
            'SYNTEC',
            'FORT DODGE',
            'BIOVET',
            'VANSIL',
            'BOEHRINGER INGELHEIM',
            'HERTAPE',
        ];
    }
}
