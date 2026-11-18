@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Protocolos de Vacinação</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total de Protocolos:</span><br>
                    <span class="info-value font-bold">{{ $protocols->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Espécies Cobertas:</span><br>
                    <span class="info-value font-bold">{{ $protocols->pluck('species')->unique()->implode(', ') ?: '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Ativos:</span><br>
                    <span class="info-value font-bold">{{ $protocols->where('is_active', true)->count() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@php $bySpecies = $protocols->groupBy('species'); @endphp
@foreach($bySpecies as $species => $speciesProtocols)
<div class="section-box">
    <div class="section-title">🐾 {{ ucfirst($species) }} ({{ $speciesProtocols->count() }} protocolo(s))</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Vacina</th>
                    <th class="text-center">Core?</th>
                    <th class="text-center">Inicial?</th>
                    <th class="text-center">Dose Nº</th>
                    <th>Idade Início</th>
                    <th>Idade Fim</th>
                    <th>Reforço</th>
                    <th>Status</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($speciesProtocols->sortBy('age_start_weeks') as $protocol)
                <tr>
                    <td><strong>{{ $protocol->vaccine_name }}</strong></td>
                    <td class="text-center">
                        @if($protocol->is_core) <span class="badge badge-success">Sim</span>
                        @else <span class="badge badge-info">Não</span> @endif
                    </td>
                    <td class="text-center">
                        @if($protocol->is_initial) <span class="badge badge-warning">Sim</span>
                        @else — @endif
                    </td>
                    <td class="text-center">{{ $protocol->dose_number ?? '—' }}</td>
                    <td>{{ $protocol->age_start_weeks !== null ? $protocol->age_start_weeks . ' sem.' : '—' }}</td>
                    <td>{{ $protocol->age_end_weeks !== null ? $protocol->age_end_weeks . ' sem.' : '—' }}</td>
                    <td>{{ $protocol->booster_interval_months ? 'A cada ' . $protocol->booster_interval_months . ' meses' : '—' }}</td>
                    <td>
                        @if($protocol->is_active) <span class="badge badge-success">Ativo</span>
                        @else <span class="badge badge-danger">Inativo</span> @endif
                    </td>
                    <td>{{ $protocol->notes ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endsection
