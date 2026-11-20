@extends('layouts.adminlte', ['title' => 'Dashboard'])

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['todayAppointments'] ?? 0 }}</h3>
                <p>Consultas Hoje</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <a href="{{ route('appointments.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['totalPets'] ?? 0 }}</h3>
                <p>Total de Pets</p>
            </div>
            <div class="icon"><i class="fas fa-paw"></i></div>
            <a href="{{ route('pets.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-purple">
            <div class="inner">
                <h3>R$ {{ number_format($stats['monthRevenue'] ?? 0, 2, ',', '.') }}</h3>
                <p>Receita do Mês</p>
            </div>
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            <a href="{{ route('invoices.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['lowStock'] ?? 0 }}</h3>
                <p>Estoque Baixo</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="{{ route('products.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['todayRevenue'] ? 'R$ ' . number_format($stats['todayRevenue'], 2, ',', '.') : 'R$ 0,00' }}</h3>
                <p>Receita Hoje</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="{{ route('invoices.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['todayProcedures'] ?? 0 }}</h3>
                <p>Atendimentos Hoje</p>
            </div>
            <div class="icon"><i class="fas fa-stethoscope"></i></div>
            <a href="{{ route('medical-records.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $stats['activeHospitalizations'] ?? 0 }}</h3>
                <p>Internados</p>
            </div>
            <div class="icon"><i class="fas fa-procedures"></i></div>
            <a href="{{ route('hospitalizations.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-pink">
            <div class="inner">
                <h3>{{ number_format($stats['noShowRate'] ?? 0, 1) }}%</h3>
                <p>Taxa de Ausência</p>
            </div>
            <div class="icon"><i class="fas fa-user-slash"></i></div>
            <a href="{{ route('appointments.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-orange">
            <div class="inner">
                <h3>{{ $stats['pendingReminders'] ?? 0 }}</h3>
                <p>Lembretes Pendentes</p>
            </div>
            <div class="icon"><i class="fas fa-bell"></i></div>
            <a href="{{ route('vaccination-reminders.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-maroon">
            <div class="inner">
                <h3>{{ $stats['overdueParasiteControls'] ?? 0 }}</h3>
                <p>Parasitários Atrasados</p>
            </div>
            <div class="icon"><i class="fas fa-bug"></i></div>
            <a href="{{ route('parasite-controls.index') }}" class="small-box-footer">Ver mais <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

@include('dashboard._vaccination_panel')
@include('vaccination-center._modals')

@endsection
