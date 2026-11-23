<?php

namespace App\Http\Controllers;

use App\Models\VaccineProtocol;
use Illuminate\Http\Request;

class VaccineProtocolController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:protocolo-vacinas');
    }
    public function index(Request $request)
    {
        $query = VaccineProtocol::query();

        if ($request->species) {
            $query->where('species', $request->species);
        }

        if ($request->is_core !== null) {
            $query->where('is_core', $request->is_core);
        }

        $protocols = $query->orderBy('species')->orderBy('age_start_weeks')->get();

        return view('vaccine-protocols.index', compact('protocols'));
    }

    public function create()
    {
        return redirect()->route('vaccine-protocols.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'protocol_type' => 'nullable|in:vaccine,antiparasitic,dewormer,medication,other',
            'species' => 'required|string|max:50',
            'vaccine_name' => 'required|string|max:200',
            'age_start_weeks' => 'nullable|integer|min:0',
            'age_end_weeks' => 'nullable|integer|min:0|gte:age_start_weeks',
            'is_initial' => 'boolean',
            'dose_number' => 'nullable|integer|min:1',
            'interval_value' => 'nullable|integer|min:1|max:3650',
            'interval_unit' => 'nullable|in:days,weeks,months|required_with:interval_value',
            'booster_interval_months' => 'nullable|integer|min:1',
            'is_core' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('vaccine_protocols', 'product_id')) {
            unset($validated['product_id'], $validated['protocol_type'], $validated['interval_value'], $validated['interval_unit']);
        }

        VaccineProtocol::create($validated);

        return redirect()->route('vaccine-protocols.index')
            ->with('success', 'Protocolo cadastrado com sucesso!');
    }

    public function show(VaccineProtocol $vaccineProtocol)
    {
        return view('vaccine-protocols.show', compact('vaccineProtocol'));
    }

    public function edit($vaccineProtocol)
    {
        return redirect()->route('vaccine-protocols.index');
    }

    public function update(Request $request, VaccineProtocol $vaccineProtocol)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'protocol_type' => 'nullable|in:vaccine,antiparasitic,dewormer,medication,other',
            'species' => 'required|string|max:50',
            'vaccine_name' => 'required|string|max:200',
            'age_start_weeks' => 'nullable|integer|min:0',
            'age_end_weeks' => 'nullable|integer|min:0|gte:age_start_weeks',
            'is_initial' => 'boolean',
            'dose_number' => 'nullable|integer|min:1',
            'interval_value' => 'nullable|integer|min:1|max:3650',
            'interval_unit' => 'nullable|in:days,weeks,months|required_with:interval_value',
            'booster_interval_months' => 'nullable|integer|min:1',
            'is_core' => 'boolean',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('vaccine_protocols', 'product_id')) {
            unset($validated['product_id'], $validated['protocol_type'], $validated['interval_value'], $validated['interval_unit']);
        }

        $vaccineProtocol->update($validated);

        return redirect()->route('vaccine-protocols.index')
            ->with('success', 'Protocolo atualizado com sucesso!');
    }

    public function destroy(VaccineProtocol $vaccineProtocol)
    {
        $vaccineProtocol->delete();

        return redirect()->route('vaccine-protocols.index')
            ->with('success', 'Protocolo excluído com sucesso!');
    }

    public function print(Request $request)
    {
        $query = VaccineProtocol::query();

        if ($request->species) {
            $query->where('species', $request->species);
        }
        if ($request->is_core !== null) {
            $query->where('is_core', $request->is_core);
        }

        $protocols = $query->orderBy('species')->orderBy('age_start_weeks')->get();

        return view('vaccine-protocols.print', [
            'protocols' => $protocols,
            'title' => 'Protocolos e Calendário de Vacinação',
            'subtitle' => 'Diretrizes Oficiais e Recomendações — Amigo Bicho',
            'pdfUrl' => route('vaccine-protocols.pdf', $request->all()),
        ]);
    }

    public function pdf(Request $request)
    {
        $query = VaccineProtocol::query();

        if ($request->species) {
            $query->where('species', $request->species);
        }
        if ($request->is_core !== null) {
            $query->where('is_core', $request->is_core);
        }

        $protocols = $query->orderBy('species')->orderBy('age_start_weeks')->get();

        return \App\Services\DocumentPdfService::download('vaccine-protocols.print', [
            'protocols' => $protocols,
            'title' => 'Protocolos e Calendário de Vacinação',
            'subtitle' => 'Diretrizes Oficiais e Recomendações — Amigo Bicho',
        ], 'protocolos-vacinacao-' . now()->format('Ymd'));
    }
}
