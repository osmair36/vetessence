@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Teleconsulta #{{ $teleconsultation->id }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Animal:</span><br>
                    <span class="info-value">{{ $teleconsultation->pet->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Tutor:</span><br>
                    <span class="info-value">{{ $teleconsultation->pet->tutor->name ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Médico Veterinário:</span><br>
                    <span class="info-value">{{ $teleconsultation->vet->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Provedor / Plataforma:</span><br>
                    <span class="info-value">{{ $teleconsultation->provider ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Agendado para:</span><br>
                    <span class="info-value">{{ $teleconsultation->scheduled_at ? \Carbon\Carbon::parse($teleconsultation->scheduled_at)->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Iniciado em:</span><br>
                    <span class="info-value">{{ $teleconsultation->started_at ? \Carbon\Carbon::parse($teleconsultation->started_at)->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Encerrado em:</span><br>
                    <span class="info-value">{{ $teleconsultation->ended_at ? \Carbon\Carbon::parse($teleconsultation->ended_at)->format('d/m/Y H:i') : '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Duração:</span><br>
                    <span class="info-value">{{ $teleconsultation->duration_minutes ? $teleconsultation->duration_minutes . ' minutos' : '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Status:</span><br>
                    <span class="info-value">
                        @if($teleconsultation->status === 'completed') <span class="badge badge-success">Concluída</span>
                        @elseif($teleconsultation->status === 'active') <span class="badge badge-info">Em andamento</span>
                        @elseif($teleconsultation->status === 'scheduled') <span class="badge badge-warning">Agendada</span>
                        @else <span class="badge badge-danger">{{ $teleconsultation->status }}</span> @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($teleconsultation->notes)
<div class="section-box">
    <div class="section-title">Observações / Condutas da Teleconsulta</div>
    <div class="section-body"><p>{{ $teleconsultation->notes }}</p></div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $teleconsultation->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">CRMV: {{ $teleconsultation->vet->crmv ?? '—' }}</div>
            </td>
        </tr>
    </table>
</div>
@endsection
