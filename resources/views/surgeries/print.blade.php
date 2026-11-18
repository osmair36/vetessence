@extends('layouts.print', [
    'title' => 'Ficha e Relatório Cirúrgico Veterinário',
    'subtitle' => 'Procedimento: ' . $surgery->surgery_type . ' — Paciente: ' . ($surgery->pet->name ?? '-'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Cirurgia #' . $surgery->id . ' - ' . ($surgery->pet->name ?? 'Pet'),
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Dados do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $surgery->pet->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($surgery->pet->species ?? '-') }} / {{ $surgery->pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $surgery->pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $surgery->pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Peso:</span> <br>{{ $surgery->pet->weight ? number_format($surgery->pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor:</span> <br><strong>{{ $surgery->pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Telefone:</span> <br>{{ $surgery->pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Microchip:</span> <br>{{ $surgery->pet->microchip ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Identificação da Cirurgia e Equipe Responsável</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 35%;"><span class="info-label">Procedimento Cirúrgico:</span> <br><strong style="font-size: 12px; color: #1E7E34;">{{ $surgery->surgery_type }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Data e Horário:</span> <br>{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('d/m/Y H:i') : '-' }}</td>
                <td style="width: 20%;"><span class="info-label">Duração Estimada:</span> <br>{{ $surgery->surgery_duration ? $surgery->surgery_duration . ' minutos' : '-' }}</td>
                <td style="width: 20%;"><span class="info-label">Status:</span> <br><span class="badge badge-success">{{ ucfirst($surgery->status) }}</span></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Cirurgião Responsável:</span> <br><strong>{{ $surgery->vet->name ?? 'Corpo Clínico' }}</strong></td>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tipo de Anestesia:</span> <br>{{ $surgery->anesthesia_type ?? 'Geral Inalatória / Injetável' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">3. Diagnóstico Pré-Operatório e Protocolo Anestésico</div>
    <div class="section-body">
        <p style="margin: 0 0 4px 0;"><strong>Diagnóstico / Indicação Cirúrgica:</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px; margin-bottom: 8px;">
            {!! nl2br(e($surgery->diagnosis ?: 'Indicação cirúrgica conforme avaliação clínica')) !!}
        </div>

        <p style="margin: 0 0 4px 0;"><strong>Protocolo Anestésico (MPA, Indução e Manutenção):</strong></p>
        <div style="background: #fafafa; border: 1px solid #eee; padding: 6px 10px; border-radius: 3px;">
            {!! nl2br(e($surgery->protocol ?: 'Protocolo padrão estabelecido pela equipe de anestesiologia')) !!}
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">4. Descrição do Ato Cirúrgico e Transoperatório</div>
    <div class="section-body">
        <div style="background: #fafafa; border: 1px solid #eee; padding: 10px; border-radius: 3px; line-height: 1.6;">
            {!! nl2br(e($surgery->surgery_notes ?: 'Procedimento cirúrgico realizado sem intercorrências transoperatórias.')) !!}
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">5. Recomendações e Cuidados Pós-Operatórios</div>
    <div class="section-body">
        <div style="background: #fafafa; border: 1px solid #eee; padding: 10px; border-radius: 3px; line-height: 1.6;">
            {!! nl2br(e($surgery->post_op_notes ?: 'Manter repouso absoluto. Uso obrigatório de colar elizabetano ou roupa cirúrgica. Administrar medicações prescritas e retornar em 10 dias para retirada de pontos.')) !!}
        </div>
    </div>
</div>

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $surgery->pet->tutors->first()?->name ?? 'Tutor / Responsável' }}</div>
                <div class="signature-sub">Ciente das orientações pós-operatórias</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $surgery->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">Cirurgião Responsável — CRMV-PR 08147</div>
            </td>
        </tr>
    </table>
</div>
@endsection
