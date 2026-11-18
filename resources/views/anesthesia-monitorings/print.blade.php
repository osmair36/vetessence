@extends('layouts.print')

@php $pageOrientation = 'landscape'; @endphp

@section('content')
<div class="section-box">
    <div class="section-title">Ficha de Monitoramento Anestésico #{{ $monitoring->id }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Animal:</span><br>
                    <span class="info-value">{{ $monitoring->pet->name ?? $monitoring->surgery->pet->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Cirurgia:</span><br>
                    <span class="info-value">{{ $monitoring->surgery->surgery_type ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Médico Veterinário:</span><br>
                    <span class="info-value">{{ $monitoring->vet->name ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Anestesista:</span><br>
                    <span class="info-value">{{ $monitoring->anesthetist ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Início do Monitoramento:</span><br>
                    <span class="info-value">{{ $monitoring->monitoring_start ? \Carbon\Carbon::parse($monitoring->monitoring_start)->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Acesso IV / Intubação:</span><br>
                    <span class="info-value">{{ $monitoring->iv_access ?? '-' }} / {{ $monitoring->intubation_type ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Protocolo Anestésico</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Protocolo:</span><br>
                    <span class="info-value">{{ $monitoring->anesthetic_protocol ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Pré-medicação (MPA):</span><br>
                    <span class="info-value">{{ $monitoring->premedication ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Agente de Indução:</span><br>
                    <span class="info-value">{{ $monitoring->induction_agent ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Agente de Manutenção:</span><br>
                    <span class="info-value">{{ $monitoring->maintenance_agent ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Tipo de Fluido:</span><br>
                    <span class="info-value">{{ $monitoring->fluid_type ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Taxa de Fluido:</span><br>
                    <span class="info-value">{{ $monitoring->fluid_rate ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($monitoring->vitalSigns && $monitoring->vitalSigns->count())
<div class="section-box">
    <div class="section-title">Registro de Sinais Vitais ({{ $monitoring->vitalSigns->count() }} aferições)</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>FC (bpm)</th>
                    <th>FR (mrm)</th>
                    <th>SpO2 (%)</th>
                    <th>PA Sist.</th>
                    <th>PA Diast.</th>
                    <th>Temp. (°C)</th>
                    <th>ETCO2</th>
                    <th>Plano Anest.</th>
                    <th>Obs.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monitoring->vitalSigns->sortBy('recorded_at') as $sign)
                <tr>
                    <td class="text-center">{{ $sign->recorded_at ? \Carbon\Carbon::parse($sign->recorded_at)->format('H:i') : '-' }}</td>
                    <td class="text-center">{{ $sign->heart_rate ?? '-' }}</td>
                    <td class="text-center">{{ $sign->respiratory_rate ?? '-' }}</td>
                    <td class="text-center">{{ $sign->spo2 ?? '-' }}</td>
                    <td class="text-center">{{ $sign->systolic_bp ?? '-' }}</td>
                    <td class="text-center">{{ $sign->diastolic_bp ?? '-' }}</td>
                    <td class="text-center">{{ $sign->temperature ?? '-' }}</td>
                    <td class="text-center">{{ $sign->etco2 ?? '-' }}</td>
                    <td class="text-center">{{ $sign->anesthesia_plane ?? '-' }}</td>
                    <td>{{ $sign->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="section-box">
    <div class="section-title">Sinais Vitais</div>
    <div class="section-body"><p style="color:#888;text-align:center;">Nenhum sinal vital registrado.</p></div>
</div>
@endif

@if($monitoring->observations)
<div class="section-box">
    <div class="section-title">Observações</div>
    <div class="section-body"><p>{{ $monitoring->observations }}</p></div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $monitoring->vet->name ?? 'Médico Veterinário Responsável' }}</div>
                <div class="signature-sub">CRMV: {{ $monitoring->vet->crmv ?? '—' }}</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $monitoring->anesthetist ?? 'Anestesista' }}</div>
                <div class="signature-sub">Responsável pela Anestesia</div>
            </td>
        </tr>
    </table>
</div>
@endsection
