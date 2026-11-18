@extends('layouts.print', [
    'title' => 'Termo de Consentimento - ' . $consentForm->consent_number,
    'subtitle' => 'Nº ' . $consentForm->consent_number . ' — Resp. Técnica: Dra. Joelma Scarabelli Neves (CRMV-PR 08147)',
    'docTitle' => 'Termo de Consentimento ' . $consentForm->consent_number,
    'pdfUrl' => $pdfUrl ?? route('consent-forms.pdf', $consentForm),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados do Paciente e Responsáveis</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Termo Nº:</span>
            <span class="info-val font-bold">{{ $consentForm->consent_number }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data:</span>
            <span class="info-val">{{ $consentForm->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-val">
                <span class="badge {{ $consentForm->status === 'signed' ? 'badge-success' : 'badge-warning' }}">
                    {{ $consentForm->status === 'signed' ? 'ASSINADO' : strtoupper($consentForm->status) }}
                </span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Paciente / Pet:</span>
            <span class="info-val font-bold">{{ $consentForm->pet->name ?? 'Não informado' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Espécie / Raça:</span>
            <span class="info-val">{{ $consentForm->pet->species ?? '-' }} / {{ $consentForm->pet->breed ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Sexo / Idade:</span>
            <span class="info-val">{{ $consentForm->pet->gender ?? '-' }} / {{ $consentForm->pet ? $consentForm->pet->age : '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Tutor / Responsável:</span>
            <span class="info-val font-bold">{{ $consentForm->client_name ?? $consentForm->tutor->name ?? 'Não informado' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">CPF / Documento:</span>
            <span class="info-val">{{ $consentForm->client_document ?? $consentForm->tutor->cpf ?? 'Não informado' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone:</span>
            <span class="info-val">{{ $consentForm->tutor->phone ?? 'Não informado' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Médico Veterinário:</span>
            <span class="info-val font-bold">{{ $consentForm->veterinarian->name ?? 'Dra. Joelma Scarabelli Neves' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">CRMV:</span>
            <span class="info-val">{{ $consentForm->veterinarian->crmv ?? 'CRMV-PR 08147' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Testemunha:</span>
            <span class="info-val">{{ $consentForm->witness->name ?? '-' }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Termo de Esclarecimento e Autorização</div>
    <div style="font-size: 11px; line-height: 1.6; text-align: justify; padding: 5px;">
        @if($consentForm->template && $consentForm->template->content)
            <div style="margin-bottom: 15px;">
                {!! $consentForm->template->content !!}
            </div>
        @endif

        @if($consentForm->signed_content)
            <div style="margin-top: 10px; padding: 8px; background: #fafafa; border: 1px solid #e0e0e0; border-radius: 4px;">
                <div class="font-bold mb-1" style="font-size: 11px; color: #132E27;">Conteúdo Específico / Condições Autorizadas:</div>
                {!! $consentForm->signed_content !!}
            </div>
        @endif

        @if(!$consentForm->template && !$consentForm->signed_content)
            <p>
                Por meio deste instrumento, eu, devidamente qualificado(a) acima como responsável pelo animal, declaro que fui plenamente informado(a) pelo Médico Veterinário responsável sobre o estado clínico do animal, os procedimentos diagnósticos, anestésicos, cirúrgicos ou terapêuticos recomendados, seus potenciais riscos, benefícios e alternativas existentes.
            </p>
            <p style="margin-top: 8px;">
                Autorizo a equipe clínica e veterinária da <strong>{{ branding_clinic_name() }}</strong> a realizar os exames, medicações, procedimentos e intervenções necessárias para o bem-estar e tratamento do paciente.
            </p>
        @endif
    </div>
</div>

@if($consentForm->notes)
<div class="section-box">
    <div class="section-title">Observações Complementares</div>
    <div style="font-size: 11px; line-height: 1.5; padding: 5px;">
        {!! $consentForm->notes !!}
    </div>
</div>
@endif

@if($consentForm->status === 'signed' && $consentForm->signed_at)
<div style="text-align: right; margin-top: 10px; margin-bottom: 25px; font-size: 10px; color: #555;">
    Documento assinado formalmente em: <strong>{{ $consentForm->signed_at->format('d/m/Y \à\s H:i') }}</strong>
</div>
@endif

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $consentForm->client_name ?? $consentForm->tutor->name ?? 'Tutor / Responsável' }}</span>
            <br>
            <span class="text-muted" style="font-size: 10px;">CPF: {{ $consentForm->client_document ?? $consentForm->tutor->cpf ?? '________________________' }}</span>
            <br>
            <span style="font-size: 10px;">Assinatura do Tutor</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $consentForm->veterinarian->name ?? 'Dra. Joelma Scarabelli Neves' }}</span>
            <br>
            <span class="text-muted" style="font-size: 10px;">{{ $consentForm->veterinarian->crmv ?? 'CRMV-PR 08147' }}</span>
            <br>
            <span style="font-size: 10px;">Médico(a) Veterinário(a)</span>
        </div>
    </div>
</div>

@if($consentForm->witness)
<div class="signature-area" style="margin-top: 30px; justify-content: center;">
    <div class="signature-col" style="max-width: 320px;">
        <div class="signature-line">
            <span class="font-bold">{{ $consentForm->witness->name }}</span>
            <br>
            <span style="font-size: 10px;">Testemunha</span>
        </div>
    </div>
</div>
@endif
@endsection
