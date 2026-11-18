<?php

namespace App\Http\Controllers;

use App\Models\PetTransport;
use App\Models\Pet;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Branch;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;

class PetTransportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:atendimentos');
    }

    public function index(Request $request)
    {
        $query = PetTransport::with(['pet', 'tutor', 'driver']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('pickup_at', $request->date);
        }

        $transports = $query->orderByDesc('pickup_at')->paginate(20);
        $statuses = PetTransport::STATUSES;

        return view('pet-transports.index', compact('transports', 'statuses'));
    }

    public function create()
    {
        $pets    = Pet::where('is_active', true)->with('tutors')->orderBy('name')->get();
        $tutors  = Tutor::orderBy('name')->get();
        $drivers = User::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('pet-transports.create', compact('pets', 'tutors', 'drivers', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pet_id'               => 'required|exists:pets,id',
            'tutor_id'             => 'nullable|exists:tutors,id',
            'branch_id'            => 'nullable|exists:branches,id',
            'driver_id'            => 'nullable|exists:users,id',
            'pickup_at'            => 'required|date',
            'delivery_at'          => 'nullable|date|after:pickup_at',
            'origin_address'       => 'required|string|max:255',
            'destination_address'  => 'required|string|max:255',
            'price'                => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
        ]);

        $transport = PetTransport::create($validated + ['status' => 'scheduled']);

        return redirect()->route('pet-transports.show', $transport)
            ->with('success', 'Táxi Pet agendado com sucesso!');
    }

    public function show(PetTransport $petTransport)
    {
        $petTransport->load(['pet.tutors', 'tutor', 'driver', 'branch', 'invoice']);
        return view('pet-transports.show', compact('petTransport'));
    }

    public function edit(PetTransport $petTransport)
    {
        $pets    = Pet::where('is_active', true)->with('tutors')->orderBy('name')->get();
        $tutors  = Tutor::orderBy('name')->get();
        $drivers = User::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $statuses = PetTransport::STATUSES;

        return view('pet-transports.edit', compact('petTransport', 'pets', 'tutors', 'drivers', 'branches', 'statuses'));
    }

    public function update(Request $request, PetTransport $petTransport)
    {
        $validated = $request->validate([
            'pet_id'               => 'required|exists:pets,id',
            'tutor_id'             => 'nullable|exists:tutors,id',
            'branch_id'            => 'nullable|exists:branches,id',
            'driver_id'            => 'nullable|exists:users,id',
            'pickup_at'            => 'required|date',
            'delivery_at'          => 'nullable|date|after:pickup_at',
            'origin_address'       => 'required|string|max:255',
            'destination_address'  => 'required|string|max:255',
            'status'               => 'required|in:scheduled,collected,in_route,delivered,cancelled',
            'price'                => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string',
        ]);

        $petTransport->update($validated);

        return redirect()->route('pet-transports.show', $petTransport)
            ->with('success', 'Táxi Pet atualizado com sucesso!');
    }

    public function destroy(PetTransport $petTransport)
    {
        if (in_array($petTransport->status, ['collected', 'in_route'])) {
            return back()->with('error', 'Não é possível excluir um transporte em andamento.');
        }

        $petTransport->delete();
        return redirect()->route('pet-transports.index')->with('success', 'Registro excluído.');
    }

    public function updateStatus(Request $request, PetTransport $petTransport)
    {
        $request->validate([
            'status' => 'required|in:scheduled,collected,in_route,delivered,cancelled',
        ]);

        $petTransport->update(['status' => $request->status]);

        return response()->json(['success' => true, 'status_label' => PetTransport::STATUSES[$request->status]]);
    }

    public function print(PetTransport $petTransport)
    {
        $petTransport->load(['pet.tutors', 'tutor', 'driver', 'branch', 'invoice']);

        return view('pet-transports.print', [
            'petTransport' => $petTransport,
            'pdfUrl' => route('pet-transports.pdf', $petTransport),
        ]);
    }

    public function pdf(PetTransport $petTransport)
    {
        $petTransport->load(['pet.tutors', 'tutor', 'driver', 'branch', 'invoice']);
        $filename = 'ordem-transporte-' . $petTransport->id . '.pdf';

        return DocumentPdfService::download('pet-transports.print', [
            'petTransport' => $petTransport,
        ], $filename);
    }
}
