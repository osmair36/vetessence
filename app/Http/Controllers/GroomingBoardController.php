<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;

class GroomingBoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:atendimentos');
    }

    public function index(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $statuses = [
            'scheduled'  => ['label' => 'Agendado',    'color' => 'blue'],
            'checked_in' => ['label' => 'Chegou',      'color' => 'indigo'],
            'in_progress'=> ['label' => 'Em Atend.',   'color' => 'yellow'],
            'completed'  => ['label' => 'Pronto',      'color' => 'green'],
            'delivered'  => ['label' => 'Entregue',    'color' => 'teal'],
            'cancelled'  => ['label' => 'Cancelado',   'color' => 'red'],
        ];

        $appointments = Appointment::with(['pet.tutors', 'groomer', 'vet'])
            ->where('type', 'grooming')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get()
            ->groupBy('status');

        $groomers = User::where('is_active', true)
            ->whereHas('role', fn($q) => $q->whereIn('slug', ['veterinario', 'tecnico', 'recepcionista', 'super-admin']))
            ->orderBy('name')
            ->get();

        return view('grooming-board.index', compact('appointments', 'statuses', 'date', 'groomers'));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:scheduled,checked_in,in_progress,completed,delivered,cancelled',
        ]);

        $appointment->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function printDay(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $appointmentsList = Appointment::with(['pet.tutors', 'groomer', 'vet', 'services.service'])
            ->where('type', 'grooming')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();

        $appointments = $appointmentsList->groupBy('status');

        return view('grooming-board.print-sheet', [
            'date' => $date,
            'appointments' => $appointments,
            'appointmentsList' => $appointmentsList,
            'pdfUrl' => route('grooming-board.pdf', ['date' => $date]),
        ]);
    }

    public function pdfDay(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $appointmentsList = Appointment::with(['pet.tutors', 'groomer', 'vet', 'services.service'])
            ->where('type', 'grooming')
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get();

        $appointments = $appointmentsList->groupBy('status');
        $filename = 'agenda-banho-tosa-' . $date . '.pdf';

        return DocumentPdfService::download('grooming-board.print-sheet', [
            'date' => $date,
            'appointments' => $appointments,
            'appointmentsList' => $appointmentsList,
        ], $filename, 'landscape');
    }

    public function printOrder(Appointment $appointment)
    {
        $appointment->load(['pet.tutors', 'groomer', 'vet', 'services.service']);

        return view('grooming.order-print', [
            'appointment' => $appointment,
            'pdfUrl' => route('grooming.order.pdf', $appointment),
        ]);
    }

    public function pdfOrder(Appointment $appointment)
    {
        $appointment->load(['pet.tutors', 'groomer', 'vet', 'services.service']);
        $filename = 'ordem-servico-banho-tosa-' . $appointment->id . '.pdf';

        return DocumentPdfService::download('grooming.order-print', [
            'appointment' => $appointment,
        ], $filename);
    }
}
