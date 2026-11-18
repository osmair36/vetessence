@extends('layouts.print', [
    'title' => 'Boletim e Ficha de Internação Veterinária',
    'subtitle' => 'Internação #' . $hospitalization->id . ' — Paciente: ' . ($hospitalization->pet->name ?? '-') . ' | Leito: ' . ($hospitalization->bed ?? '-'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Internação #' . $hospitalization->id . ' - ' . ($hospitalization->pet->name ?? 'Pet'),
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Dados do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $hospitalization->pet->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($hospitalization->pet->species ?? '-') }} / {{ $hospitalization->pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $hospitalization->pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $hospitalization->pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Peso Admissão:</span> <br>{{ $hospitalization->pet->weight ? number_format($hospitalization->pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor:</span> <br><strong>{{ $hospitalization->tutor->name ?? $hospitalization->pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Telefone de Contato:</span> <br>{{ $hospitalization->tutor->phone ?? $hospitalization->pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Microchip:</span> <br>{{ $hospitalization->pet->microchip ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Dados da Admissão Hospitalar</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Data e Hora de Entrada:</span> <br><strong>{{ $hospitalization->admission_date ? $hospitalization->admission_date->format('d/m/Y') : '-' }} @if($hospitalization->admission_time) às {{ $hospitalization->admission_time }} @endif</strong></td>
                <td style="width: 25%;"><span class="info-label">Setor / Leito:</span> <br>{{ $hospitalization->department ?? 'Geral' }} / Leito: {{ $hospitalization->bed ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Emergência:</span> <br><span class="badge {{ $hospitalization->is_emergency ? 'badge-danger' : 'badge-info' }}">{{ $hospitalization->is_emergency ? 'SIM (EMERGÊNCIA)' : 'Não' }}</span></td>
                <td style="width: 25%;"><span class="info-label">Situação Atual:</span> <br><span class="badge badge-success">{{ ucfirst($hospitalization->status) }}</span></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Motivo da Internação:</span> <br>{{ $hospitalization->admission_reason }}</td>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Diagnóstico Inicial:</span> <br><strong>{{ $hospitalization->initial_diagnosis ?: 'A esclarecer' }}</strong></td>
            </tr>
        </table>
    </div>
</div>

{{-- EVOLUÇÃO DIÁRIA --}}
<div class="section-box">
    <div class="section-title">3. Evolução Diária e Registros de Parâmetros Clínicos</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 15%;">Data / Hora</th>
                    <th style="width: 15%;">Temp / FC / FR</th>
                    <th style="width: 45%;">Evolução Clínica / Anotações</th>
                    <th style="width: 25%;">Profissional</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hospitalization->dailyRecords as $dr)
                <tr>
                    <td>{{ $dr->record_date ? \Carbon\Carbon::parse($dr->record_date)->format('d/m/Y H:i') : $dr->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        {{ $dr->temperature ? $dr->temperature . '°C' : '-' }}<br>
                        <small>{{ $dr->heart_rate ? $dr->heart_rate . ' bpm' : '' }} {{ $dr->respiratory_rate ? '| ' . $dr->respiratory_rate . ' mpm' : '' }}</small>
                    </td>
                    <td>{{ $dr->notes ?? $dr->clinical_evolution ?? '-' }}</td>
                    <td>{{ $dr->user->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center" style="color: #777;">Nenhum registro diário lançado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- FLUIDOTERAPIA --}}
@if($hospitalization->fluidTherapies && $hospitalization->fluidTherapies->isNotEmpty())
<div class="section-box">
    <div class="section-title">4. Controle de Fluidoterapia</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 30%;">Solução</th>
                    <th style="width: 20%;">Taxa de Infusão</th>
                    <th style="width: 20%;">Volume Total</th>
                    <th style="width: 30%;">Aditivos / Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hospitalization->fluidTherapies as $ft)
                <tr>
                    <td><strong>{{ $ft->solution_type }}</strong></td>
                    <td>{{ $ft->rate_ml_hr ? $ft->rate_ml_hr . ' ml/h' : '-' }}</td>
                    <td>{{ $ft->total_volume_ml ? $ft->total_volume_ml . ' ml' : '-' }}</td>
                    <td>{{ $ft->additives ?? $ft->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- PRESCRIÇÕES HOSPITALARES --}}
@if($hospitalization->prescriptions && $hospitalization->prescriptions->isNotEmpty())
<div class="section-box">
    <div class="section-title">5. Prescrições Hospitalares e Medicamentos</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 30%;">Medicamento</th>
                    <th style="width: 20%;">Dose / Via</th>
                    <th style="width: 25%;">Frequência / Horários</th>
                    <th style="width: 25%;">Instruções</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hospitalization->prescriptions as $hp)
                <tr>
                    <td><strong>{{ $hp->medication_name }}</strong></td>
                    <td>{{ $hp->dose }} ({{ ucfirst($hp->route ?? 'VO') }})</td>
                    <td>{{ $hp->frequency }}</td>
                    <td>{{ $hp->instructions ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($hospitalization->discharge_summary || $hospitalization->discharge_instructions)
<div class="section-box">
    <div class="section-title" style="background: #d4edda; color: #155724;">6. Resumo de Alta Médica e Orientações Domiciliares</div>
    <div class="section-body">
        @if($hospitalization->discharged_at)
            <p style="margin: 0 0 6px 0;"><strong>Data da Alta:</strong> {{ $hospitalization->discharged_at->format('d/m/Y') }}</p>
        @endif
        @if($hospitalization->discharge_summary)
            <p style="margin: 0 0 6px 0;"><strong>Resumo Clínico da Alta:</strong><br>{!! nl2br(e($hospitalization->discharge_summary)) !!}</p>
        @endif
        @if($hospitalization->discharge_instructions)
            <p style="margin: 0;"><strong>Orientações ao Tutor para Cuidados em Casa:</strong><br>{!! nl2br(e($hospitalization->discharge_instructions)) !!}</p>
        @endif
    </div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $hospitalization->tutor->name ?? 'Tutor / Responsável' }}</div>
                <div class="signature-sub">Ciente da evolução e orientações de internação</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $hospitalization->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">Veterinário Responsável pela Internação — CRMV-PR 08147</div>
            </td>
        </tr>
    </table>
</div>
@endsection
