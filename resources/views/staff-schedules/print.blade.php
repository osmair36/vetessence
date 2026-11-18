@extends('layouts.print')

@php $pageOrientation = 'landscape'; @endphp

@section('content')
<div class="section-box">
    <div class="section-title">Escala da Equipe — {{ isset($month) ? \Carbon\Carbon::parse($month.'-01')->format('F \d\e Y') : 'Relatório Geral' }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total de Escalas:</span><br>
                    <span class="info-value font-bold">{{ $schedules->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Profissionais:</span><br>
                    <span class="info-value font-bold">{{ $schedules->pluck('user.name')->unique()->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Período:</span><br>
                    <span class="info-value">
                        {{ $schedules->min('work_date') ? \Carbon\Carbon::parse($schedules->min('work_date'))->format('d/m/Y') : '-' }}
                        a
                        {{ $schedules->max('work_date') ? \Carbon\Carbon::parse($schedules->max('work_date'))->format('d/m/Y') : '-' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Escala Detalhada</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Dia</th>
                    <th>Profissional</th>
                    <th>Turno</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Unidade</th>
                    <th>Tipo</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schedules->sortBy('work_date') as $schedule)
                <tr>
                    <td>{{ $schedule->work_date ? \Carbon\Carbon::parse($schedule->work_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $schedule->work_date ? \Carbon\Carbon::parse($schedule->work_date)->isoFormat('ddd') : '-' }}</td>
                    <td>{{ $schedule->user->name ?? '-' }}</td>
                    <td>{{ $schedule->shift_type ?? '-' }}</td>
                    <td class="text-center">{{ $schedule->start_time ?? '-' }}</td>
                    <td class="text-center">{{ $schedule->end_time ?? '-' }}</td>
                    <td>{{ $schedule->branch->name ?? '-' }}</td>
                    <td>
                        @if($schedule->is_on_call) <span class="badge badge-warning">Plantão</span>
                        @elseif($schedule->is_vet_shift) <span class="badge badge-info">Veterinário</span>
                        @else <span class="badge badge-success">Regular</span> @endif
                    </td>
                    <td>{{ $schedule->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@php $byUser = $schedules->groupBy(fn($s) => $s->user->name ?? 'Sem usuário'); @endphp
<div class="section-box">
    <div class="section-title">Resumo por Profissional</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Profissional</th>
                    <th class="text-center">Total de Dias</th>
                    <th class="text-center">Plantões</th>
                    <th class="text-center">Turnos Vet</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byUser as $name => $userSchedules)
                <tr>
                    <td>{{ $name }}</td>
                    <td class="text-center">{{ $userSchedules->count() }}</td>
                    <td class="text-center">{{ $userSchedules->where('is_on_call', true)->count() }}</td>
                    <td class="text-center">{{ $userSchedules->where('is_vet_shift', true)->count() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
