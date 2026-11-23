<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Hospitalization;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Pet;
use App\Models\Product;
use App\Models\VaccinationReminder;
use App\Models\ParasiteControl;
use App\Services\Vaccination\VaccinationNotificationCenterService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(VaccinationNotificationCenterService $vaccineCenter)
    {
        $vaccineStats = $vaccineCenter->getDashboardStats();
        $todayVaccinations = $vaccineCenter->getTodayVaccinations(15);
        $todayBirthdays = $vaccineCenter->getTodayBirthdays(10);
        $todayGroomings = $vaccineCenter->getTodayGroomingAppointments(10);
        // Stats
        $stats = [
            'todayAppointments' => Appointment::where('date', today())
                ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                ->count(),
            'totalPets' => Pet::where('is_active', true)->count(),
            'monthRevenue' => Invoice::whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->where('status', 'paid')
                ->sum('total'),
            'lowStock' => Product::whereColumn('stock', '<=', 'min_stock')
                ->where('is_active', true)
                ->count(),
            'todayRevenue' => Invoice::whereDate('paid_at', today())
                ->where('status', 'paid')
                ->sum('total'),
            'todayProcedures' => MedicalRecord::whereDate('created_at', today())->count(),
            'activeHospitalizations' => Hospitalization::where('status', 'admitted')->count(),
            'overdueParasiteControls' => ParasiteControl::whereNotNull('next_due_date')
                ->where('next_due_date', '<', today())
                ->count(),
        ];

        return view('dashboard', compact(
            'stats',
            'vaccineStats',
            'todayVaccinations',
            'todayBirthdays',
            'todayGroomings'
        ));
    }
}
