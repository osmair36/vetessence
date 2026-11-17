<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
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
}
