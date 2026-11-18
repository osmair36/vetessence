@extends('layouts.print', [
    'title' => 'Laudo de Exame por Imagem',
    'subtitle' => 'Exame ' . $exam->exam_number . ' — Modalidade: ' . ucfirst($exam->exam_type ?? 'Imagem') . ' (' . ($exam->region ?? 'Região Geral') . ')',
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Laudo de Imagem - ' . $exam->exam_number,
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Dados do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $exam->pet->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($exam->pet->species ?? '-') }} / {{ $exam->pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $exam->pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $exam->pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Microchip:</span> <br>{{ $exam->pet->microchip ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor:</span> <br><strong>{{ $exam->pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Veterinário Solicitante:</span> <br>{{ $exam->vet->name ?? 'Corpo Clínico' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Data do Exame:</span> <br>{{ $exam->exam_date ? $exam->exam_date->format('d/m/Y') : $exam->created_at->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Identificação do Procedimento e Modalidade</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 30%;"><span class="info-label">Nº do Exame:</span> <br><strong>{{ $exam->exam_number }}</strong></td>
                <td style="width: 35%;"><span class="info-label">Modalidade do Exame:</span> <br><strong>{{ ucfirst($exam->exam_type ?? 'Radiografia / Ultrassonografia') }}</strong></td>
                <td style="width: 35%;"><span class="info-label">Região Anatômica Avaliada:</span> <br><strong>{{ $exam->region ?? 'Não informada' }}</strong></td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">3. Descrição dos Achados Radiográficos / Ecográficos</div>
    <div class="section-body" style="line-height: 1.6;">
        <div style="background: #fafafa; border: 1px solid #eee; padding: 10px; border-radius: 3px;">
            {!! nl2br(e($exam->findings ?: 'Sem achados anormais descritos.')) !!}
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">4. Impressão Diagnóstica / Conclusão</div>
    <div class="section-body" style="line-height: 1.6;">
        <div style="background: #fafafa; border: 1px solid #eee; padding: 10px; border-radius: 3px; font-weight: bold; color: #111;">
            {!! nl2br(e($exam->impression ?: 'Exame dentro dos limites da normalidade para a espécie.')) !!}
        </div>
    </div>
</div>

@if($exam->recommendations)
<div class="section-box">
    <div class="section-title">5. Recomendações Clínicas Complementares</div>
    <div class="section-body">
        {!! nl2br(e($exam->recommendations)) !!}
    </div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $exam->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">Veterinário Solicitante</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $exam->radiologist->name ?? 'Dra. Joelma Scarabelli Neves' }}</div>
                <div class="signature-sub">Médico Veterinário Imaginologista — CRMV-PR 08147</div>
            </td>
        </tr>
    </table>
</div>
@endsection
