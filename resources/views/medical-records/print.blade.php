@extends('layouts.print', [
    'title' => 'Prontuário de Atendimento Clínico',
    'subtitle' => 'Atendimento #' . $record->id . ' — Data: ' . ($record->date ? $record->date->format('d/m/Y') : $record->created_at->format('d/m/Y')),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Atendimento #' . $record->id . ' - ' . ($record->pet->name ?? 'Pet'),
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Identificação do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $record->pet->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($record->pet->species ?? '-') }} / {{ $record->pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $record->pet->gender === 'male' ? 'Macho' : ($record->pet->gender === 'female' ? 'Fêmea' : '-') }} | {{ $record->pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Peso:</span> <br>{{ $record->pet->weight ? number_format($record->pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 8px;">
                    <span class="info-label">Tutor:</span> <br>
                    <strong>{{ $record->pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong>
                </td>
                <td style="padding-top: 8px;"><span class="info-label">Telefone:</span> <br>{{ $record->pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Microchip:</span> <br>{{ $record->pet->microchip ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Queixa Principal e Anamnese</div>
    <div class="section-body">
        <p style="margin: 0 0 6px 0;"><strong>Queixa Principal:</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px; margin-bottom: 8px;">
            {!! nl2br(e($record->chief_complaint ?: 'Não informada')) !!}
        </div>

        <p style="margin: 0 0 6px 0;"><strong>Anamnese e Histórico Clínico:</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px;">
            {!! nl2br(e($record->anamnesis ?: 'Sem anotações de anamnese')) !!}
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">3. Exame Físico e Sinais Vitais</div>
    <div class="section-body">
        @if(is_array($record->vital_signs) && count($record->vital_signs) > 0)
        <table class="table-doc" style="margin-top: 0; margin-bottom: 8px;">
            <thead>
                <tr>
                    <th>Temp. Retal</th>
                    <th>FC (BPM)</th>
                    <th>FR (MPM)</th>
                    <th>TPC</th>
                    <th>Mucosas</th>
                    <th>Hidratação</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $record->vital_signs['temperature'] ?? '-' }} °C</td>
                    <td>{{ $record->vital_signs['heart_rate'] ?? '-' }} bpm</td>
                    <td>{{ $record->vital_signs['respiratory_rate'] ?? '-' }} mpm</td>
                    <td>{{ $record->vital_signs['tpc'] ?? '-' }} seg</td>
                    <td>{{ ucfirst($record->vital_signs['mucous_membranes'] ?? '-') }}</td>
                    <td>{{ ucfirst($record->vital_signs['hydration'] ?? '-') }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <p style="margin: 0 0 4px 0;"><strong>Achados do Exame Clínico:</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px;">
            {!! nl2br(e($record->physical_exam ?: 'Nenhuma alteração observada.')) !!}
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">4. Avaliação Diagnóstica e Prognóstico</div>
    <div class="section-body">
        <p style="margin: 0 0 4px 0;"><strong>Diagnóstico / Suspeitas Clínicas:</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px; margin-bottom: 8px; font-weight: bold; color: #111;">
            {!! nl2br(e($record->diagnosis ?: 'Em investigação diagnóstica')) !!}
        </div>

        <p style="margin: 0 0 4px 0;"><strong>Prognóstico:</strong> <span class="badge badge-info">{{ ucfirst($record->prognosis ?? 'Indeterminado') }}</span></p>
    </div>
</div>

<div class="section-box">
    <div class="section-title">5. Conduta Médica e Plano Terapêutico</div>
    <div class="section-body">
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px;">
            {!! nl2br(e($record->treatment ?: 'Sem prescrição registrada no atendimento.')) !!}
        </div>
    </div>
</div>

@if($record->prescriptions && $record->prescriptions->isNotEmpty())
<div class="section-box">
    <div class="section-title">6. Prescrições Vinculadas a este Atendimento</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 25%;">Medicamento</th>
                    <th style="width: 20%;">Dose / Concentração</th>
                    <th style="width: 20%;">Posologia</th>
                    <th style="width: 20%;">Duração</th>
                    <th style="width: 15%;">Controle</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->prescriptions as $rx)
                    @if(is_array($rx->items) || is_object($rx->items))
                        @foreach($rx->items as $item)
                        <tr>
                            <td><strong>{{ $item['medicine_name'] ?? $item['medication'] ?? '-' }}</strong></td>
                            <td>{{ $item['dose'] ?? '-' }}</td>
                            <td>{{ $item['frequency'] ?? '-' }}</td>
                            <td>{{ $item['duration'] ?? '-' }}</td>
                            <td>{{ $rx->is_controlled ? 'CONTROLADO' : 'Comum' }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4">{{ $rx->instructions ?? $rx->notes ?? 'Prescrição vinculada #' . $rx->id }}</td>
                            <td>{{ $rx->is_controlled ? 'CONTROLADO' : 'Comum' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($record->notes)
<div class="section-box">
    <div class="section-title">7. Observações e Orientações Gerais ao Tutor</div>
    <div class="section-body">
        {!! nl2br(e($record->notes)) !!}
    </div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $record->pet->tutors->first()?->name ?? 'Tutor / Responsável' }}</div>
                <div class="signature-sub">Ciente das orientações médicas</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $record->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">CRMV: {{ $record->vet->crmv ?? 'CRMV-PR 08147' }}</div>
            </td>
        </tr>
    </table>
</div>
@endsection
