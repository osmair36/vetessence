@extends('layouts.print', [
    'title' => 'Ordem de Transporte Pet #' . $petTransport->id,
    'subtitle' => 'Táxi Dog / Transporte Amigo Bicho — OS #' . str_pad($petTransport->id, 5, '0', STR_PAD_LEFT),
    'docTitle' => 'Ordem Transporte ' . $petTransport->id,
    'pdfUrl' => $pdfUrl ?? route('pet-transports.pdf', $petTransport),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados da Ordem de Transporte & Motorista</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Transporte Nº:</span>
            <span class="info-val font-bold">#{{ str_pad($petTransport->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data / Coleta Prevista:</span>
            <span class="info-val font-bold">{{ $petTransport->pickup_at ? $petTransport->pickup_at->format('d/m/Y H:i') : '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-val">
                <span class="badge badge-info">{{ $petTransport->status_label ?? $petTransport->status }}</span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Motorista / Condutor:</span>
            <span class="info-val font-bold">{{ $petTransport->driver->name ?? 'A definir' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Unidade Responsável:</span>
            <span class="info-val">{{ $petTransport->branch->name ?? branding_clinic_name() }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Valor do Transporte:</span>
            <span class="info-val font-bold text-primary">R$ {{ number_format($petTransport->price, 2, ',', '.') }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Dados do Animal & Tutor</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Paciente / Pet:</span>
            <span class="info-val font-bold" style="font-size: 13px;">{{ $petTransport->pet->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Espécie / Raça:</span>
            <span class="info-val">{{ $petTransport->pet->species ?? '-' }} / {{ $petTransport->pet->breed ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Porte / Peso:</span>
            <span class="info-val">{{ $petTransport->pet->size ?? '-' }} / {{ $petTransport->pet->weight ? number_format($petTransport->pet->weight, 2, ',', '.') . ' kg' : '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Tutor / Contratante:</span>
            <span class="info-val font-bold">{{ $petTransport->tutor->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone de Contato:</span>
            <span class="info-val font-bold">{{ $petTransport->tutor->phone ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">CPF:</span>
            <span class="info-val">{{ $petTransport->tutor->cpf ?? '-' }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Rota de Transporte (Coleta & Destino)</div>
    <div style="margin-bottom: 10px; padding: 8px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
        <div style="font-size: 11px; margin-bottom: 6px;">
            <strong style="color: #132E27;"><i class="fas fa-map-marker-alt"></i> Ponto de Origem / Coleta:</strong>
            <div style="font-size: 12px; font-weight: bold; margin-top: 2px;">{{ $petTransport->origin_address }}</div>
        </div>
        <div style="font-size: 10px; color: #555;">
            Horário agendado para embarque: <strong>{{ $petTransport->pickup_at ? $petTransport->pickup_at->format('d/m/Y H:i') : '-' }}</strong>
        </div>
    </div>

    <div style="padding: 8px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
        <div style="font-size: 11px; margin-bottom: 6px;">
            <strong style="color: #28a745;"><i class="fas fa-flag-checkered"></i> Ponto de Destino / Entrega:</strong>
            <div style="font-size: 12px; font-weight: bold; margin-top: 2px;">{{ $petTransport->destination_address }}</div>
        </div>
        <div style="font-size: 10px; color: #555;">
            Horário previsto para entrega: <strong>{{ $petTransport->delivery_at ? $petTransport->delivery_at->format('d/m/Y H:i') : 'Conforme rota' }}</strong>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Checklist de Embarque & Pertences Acompanhantes</div>
    <table class="table-doc" style="font-size: 10px;">
        <thead>
            <tr>
                <th style="width: 25%;">Item / Verificação</th>
                <th style="width: 25%;">Estado no Embarque</th>
                <th style="width: 25%;">Item / Verificação</th>
                <th style="width: 25%;">Estado no Desembarque</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Caixa de Transporte:</strong></td>
                <td>( ) Própria do tutor &nbsp; ( ) Da clínica</td>
                <td><strong>Comportamento na Viagem:</strong></td>
                <td>( ) Calmo &nbsp; ( ) Ansioso &nbsp; ( ) Reativo</td>
            </tr>
            <tr>
                <td><strong>Coleira / Guia / Peitoral:</strong></td>
                <td>( ) Sim &nbsp; ( ) Não</td>
                <td><strong>Pertences Devolvidos:</strong></td>
                <td>( ) Todos conferidos</td>
            </tr>
            <tr>
                <td><strong>Medicação / Ração:</strong></td>
                <td>( ) Acompanha &nbsp; ( ) Não</td>
                <td><strong>Horário Real de Entrega:</strong></td>
                <td>____:____</td>
            </tr>
        </tbody>
    </table>
</div>

@if($petTransport->notes)
<div class="section-box">
    <div class="section-title">Instruções e Observações Especiais</div>
    <div style="font-size: 11px; padding: 6px; line-height: 1.5;">
        {!! nl2br(e($petTransport->notes)) !!}
    </div>
</div>
@endif

<div class="section-box">
    <div class="section-title">Comprovante de Embarque e Entrega</div>
    <p style="font-size: 9.5px; line-height: 1.4; color: #444; margin: 0 0 8px 0; text-align: justify;">
        Autorizo o transporte do animal especificado nesta guia até o endereço de destino indicado. Declaro que o animal foi embarcado e recebido em conformidade com as condições acordadas.
    </p>
</div>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $petTransport->tutor->name ?? 'Tutor / Responsável' }}</span>
            <br>
            <span style="font-size: 10px;">Assinatura do Tutor (Recebimento)</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $petTransport->driver->name ?? 'Motorista / Condutor' }}</span>
            <br>
            <span style="font-size: 10px;">Transporte Táxi Dog — Amigo Bicho</span>
        </div>
    </div>
</div>
@endsection
