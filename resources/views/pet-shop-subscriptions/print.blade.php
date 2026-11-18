@extends('layouts.print', [
    'title' => 'Ficha de Pacote Pet Shop — ' . $petShopSubscription->package->name,
    'subtitle' => 'Contrato #' . $petShopSubscription->id . ' — ' . ($petShopSubscription->pet->name ?? 'Pet'),
    'docTitle' => 'Contrato Pacote ' . $petShopSubscription->id,
    'pdfUrl' => $pdfUrl ?? route('pet-shop-subscriptions.pdf', $petShopSubscription),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados do Contrato de Pacote / Assinatura</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Contrato Nº:</span>
            <span class="info-val font-bold">#{{ str_pad($petShopSubscription->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Pacote:</span>
            <span class="info-val font-bold" style="font-size: 13px;">{{ $petShopSubscription->package->name }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status da Assinatura:</span>
            <span class="info-val">
                <span class="badge {{ $petShopSubscription->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                    {{ $petShopSubscription->status === 'active' ? 'ATIVO' : strtoupper($petShopSubscription->status) }}
                </span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Início da Vigência:</span>
            <span class="info-val">{{ $petShopSubscription->start_date ? $petShopSubscription->start_date->format('d/m/Y') : '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Validade / Término:</span>
            <span class="info-val font-bold text-primary">{{ $petShopSubscription->end_date ? $petShopSubscription->end_date->format('d/m/Y') : 'Indeterminado' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Renovação Automática:</span>
            <span class="info-val">{{ $petShopSubscription->auto_renew ? 'Sim' : 'Não' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Paciente / Pet:</span>
            <span class="info-val font-bold">{{ $petShopSubscription->pet->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Espécie / Raça:</span>
            <span class="info-val">{{ $petShopSubscription->pet->species ?? '-' }} / {{ $petShopSubscription->pet->breed ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Tutor Responsável:</span>
            <span class="info-val font-bold">{{ $petShopSubscription->pet->tutors->first()->name ?? '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Telefone / WhatsApp:</span>
            <span class="info-val">{{ $petShopSubscription->pet->tutors->first()->phone ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Valor do Pacote:</span>
            <span class="info-val font-bold">R$ {{ number_format($petShopSubscription->package->total_price, 2, ',', '.') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Economia Estimada:</span>
            <span class="info-val text-success font-bold">R$ {{ number_format($petShopSubscription->total_savings, 2, ',', '.') }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Controle de Saldo e Utilização dos Serviços</div>
    <div style="display: flex; gap: 15px; margin-bottom: 12px;">
        <div style="flex: 1; border: 1px solid #17a2b8; border-radius: 4px; padding: 10px; text-align: center; background: #f0faff;">
            <div style="font-size: 10px; color: #555; text-transform: uppercase;">Total Contratado</div>
            <div style="font-size: 18px; font-weight: bold; color: #17a2b8;">{{ $petShopSubscription->total_uses }} utilizações</div>
        </div>
        <div style="flex: 1; border: 1px solid #28a745; border-radius: 4px; padding: 10px; text-align: center; background: #f0fff4;">
            <div style="font-size: 10px; color: #555; text-transform: uppercase;">Utilizados até o momento</div>
            <div style="font-size: 18px; font-weight: bold; color: #28a745;">{{ $petShopSubscription->total_uses - $petShopSubscription->remaining_uses }}</div>
        </div>
        <div style="flex: 1; border: 1px solid #ffc107; border-radius: 4px; padding: 10px; text-align: center; background: #fffdf0;">
            <div style="font-size: 10px; color: #555; text-transform: uppercase;">Saldo Restante</div>
            <div style="font-size: 18px; font-weight: bold; color: #d39e00;">{{ $petShopSubscription->remaining_uses }} créditos</div>
        </div>
    </div>

    @if($petShopSubscription->package->description)
    <div style="font-size: 11px; padding: 6px; background: #fafafa; border-radius: 4px; margin-bottom: 10px;">
        <strong>Descrição do Pacote:</strong> {{ $petShopSubscription->package->description }}
    </div>
    @endif
</div>

<div class="section-box">
    <div class="section-title">Histórico de Consumos Registrados</div>
    @if($petShopSubscription->consumptions && $petShopSubscription->consumptions->count() > 0)
    <table class="table-doc">
        <thead>
            <tr>
                <th style="width: 15%;">Data de Uso</th>
                <th style="width: 35%;">Serviço Consumido</th>
                <th style="width: 25%;">Economia Gerada</th>
                <th style="width: 25%;">Atendente / Responsável</th>
            </tr>
        </thead>
        <tbody>
            @foreach($petShopSubscription->consumptions->sortByDesc('service_date') as $c)
            <tr>
                <td class="font-bold">{{ $c->service_date ? $c->service_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $c->service->name ?? 'Serviço do Pacote' }}</td>
                <td class="text-success font-bold">R$ {{ number_format($c->savings_amount, 2, ',', '.') }}</td>
                <td>{{ $c->user->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="padding: 10px; text-align: center; color: #777; font-size: 11px;">
        Nenhum consumo registrado até o momento para este contrato.
    </p>
    @endif
</div>

<div class="section-box">
    <div class="section-title">Termo de Adesão e Condições Gerais</div>
    <div style="font-size: 9.5px; line-height: 1.5; color: #444; text-align: justify; padding: 4px;">
        <p>1. Os serviços contratados neste pacote são de uso exclusivo para o animal identificado neste instrumento e deverão ser usufruídos dentro do prazo de validade estipulado.</p>
        <p>2. O agendamento prévio dos serviços é necessário e está sujeito à disponibilidade de horários da clínica / pet shop.</p>
        <p>3. Não haverá reembolso por serviços não utilizados após a expiração da validade do pacote, salvo acordo formal com a administração da clínica.</p>
    </div>
</div>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $petShopSubscription->pet->tutors->first()->name ?? 'Tutor Responsável' }}</span>
            <br>
            <span style="font-size: 10px;">Assinatura do Tutor</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ branding_clinic_name() }}</span>
            <br>
            <span style="font-size: 10px;">Amigo Bicho — Setor de Atendimento</span>
        </div>
    </div>
</div>
@endsection
