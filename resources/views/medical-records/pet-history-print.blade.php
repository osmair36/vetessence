@extends('layouts.print', [
    'title' => 'Histórico Completo do Prontuário Médico',
    'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Prontuário - ' . $pet->name,
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados de Identificação do Animal e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Nome:</span> <br><strong style="font-size: 13px;">{{ $pet->name }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($pet->species ?? '-') }} / {{ $pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Microchip:</span> <br>{{ $pet->microchip ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor(es):</span> <br><strong>{{ $pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Contato:</span> <br>{{ $pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Total de Atendimentos:</span> <br><strong>{{ $pet->medicalRecords->count() }}</strong></td>
            </tr>
        </table>
    </div>
</div>

<h3 style="font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 15px 0 8px 0; border-bottom: 2px solid #1E7E34; padding-bottom: 4px; color: #132E27;">
    Evolução Cronológica de Atendimentos
</h3>

@forelse($pet->medicalRecords as $index => $record)
<div class="section-box" style="margin-bottom: 15px; border-left: 3px solid #1E7E34;">
    <div class="section-title" style="display: flex; justify-content: space-between;">
        <span><strong>#{{ $record->id }}</strong> — Atendimento em {{ $record->date ? $record->date->format('d/m/Y') : $record->created_at->format('d/m/Y') }} @if($record->time) às {{ $record->time }} @endif</span>
        <span style="font-size: 10px; color: #555;">Veterinário: {{ $record->vet->name ?? '-' }}</span>
    </div>
    <div class="section-body">
        @if($record->chief_complaint)
            <p style="margin: 0 0 4px 0;"><strong>Queixa Principal:</strong> {{ $record->chief_complaint }}</p>
        @endif
        @if($record->anamnesis)
            <p style="margin: 0 0 4px 0;"><strong>Anamnese:</strong> {{ $record->anamnesis }}</p>
        @endif
        @if($record->physical_exam)
            <p style="margin: 0 0 4px 0;"><strong>Exame Físico:</strong> {{ $record->physical_exam }}</p>
        @endif
        @if($record->diagnosis)
            <p style="margin: 0 0 4px 0;"><strong>Diagnóstico:</strong> <span style="font-weight: bold; color: #1E7E34;">{{ $record->diagnosis }}</span> @if($record->prognosis) (Prognóstico: {{ ucfirst($record->prognosis) }}) @endif</p>
        @endif
        @if($record->treatment)
            <p style="margin: 0 0 4px 0;"><strong>Conduta / Tratamento:</strong> {{ $record->treatment }}</p>
        @endif
        @if($record->prescriptions && $record->prescriptions->isNotEmpty())
            <div style="margin-top: 6px; padding: 4px 8px; background: #f8f9fa; border: 1px dashed #ccc; font-size: 10px;">
                <strong>Prescrições:</strong>
                @foreach($record->prescriptions as $rx)
                    @if(is_array($rx->items))
                        @foreach($rx->items as $it)
                            <div>• {{ $it['medicine_name'] ?? $it['medication'] ?? '-' }} — {{ $it['dose'] ?? '' }} ({{ $it['frequency'] ?? '' }} por {{ $it['duration'] ?? '' }})</div>
                        @endforeach
                    @else
                        <div>• {{ $rx->instructions ?? 'Prescrição #' . $rx->id }}</div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
@empty
<div class="section-box">
    <div class="section-body text-center" style="color: #777; padding: 25px;">
        Nenhum atendimento clínico registrado no prontuário deste paciente.
    </div>
</div>
@endforelse

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $pet->tutors->first()?->name ?? 'Tutor / Responsável' }}</div>
                <div class="signature-sub">Responsável pelo Paciente</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ branding_clinic_name() }}</div>
                <div class="signature-sub">Responsável Técnico / Corpo Clínico</div>
            </td>
        </tr>
    </table>
</div>
@endsection
