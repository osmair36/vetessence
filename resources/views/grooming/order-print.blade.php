@extends('layouts.print', [
    'title' => 'Ordem de Serviço — Banho & Tosa #' . $appointment->id,
    'subtitle' => 'OS Nº ' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT) . ' — ' . branding_clinic_name(),
    'docTitle' => 'OS Banho e Tosa ' . $appointment->id,
    'pdfUrl' => $pdfUrl ?? route('grooming.order.pdf', $appointment),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados da Ordem de Serviço & Paciente</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">OS Nº:</span>
            <span class="info-val font-bold">#{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data / Horário:</span>
            <span class="info-val font-bold">{{ $appointment->date ? $appointment->date->format('d/m/Y') : '-' }} às {{ \Carbon\Carbon::parse($appointment->time)->format('H:i') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status do Atendimento:</span>
            <span class="info-val">
                @php
                    $statusMap = [
                        'scheduled' => 'Agendado',
                        'checked_in' => 'Na Recepção',
                        'in_progress' => 'Em Atendimento',
                        'completed' => 'Pronto / Aguardando',
                        'delivered' => 'Entregue ao Tutor',
                        'cancelled' => 'Cancelado',
                    ];
                @endphp
                <span class="badge badge-info">{{ $statusMap[$appointment->status] ?? $appointment->status }}</span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Paciente / Pet:</span>
            <span class="info-val font-bold" style="font-size: 13px;">{{ $appointment->pet->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Espécie / Raça:</span>
            <span class="info-val">{{ $appointment->pet->species ?? '-' }} / {{ $appointment->pet->breed ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Porte / Peso:</span>
            <span class="info-val">{{ $appointment->pet->size ?? '-' }} / {{ $appointment->pet->weight ? number_format($appointment->pet->weight, 2, ',', '.') . ' kg' : '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Tutor:</span>
            <span class="info-val font-bold">{{ $appointment->pet->tutors->first()->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone / Celular:</span>
            <span class="info-val font-bold">{{ $appointment->pet->tutors->first()->phone ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Profissional / Tosador:</span>
            <span class="info-val font-bold">{{ $appointment->groomer->name ?? $appointment->vet->name ?? 'Não definido' }}</span>
        </div>
    </div>
</div>

@if($appointment->pet && ($appointment->pet->allergies || $appointment->pet->medical_notes || $appointment->pet->behavior_notes))
<div class="section-box" style="border-left: 4px solid #dc3545; background: #fff8f8;">
    <div class="section-title" style="color: #dc3545;">Alertas & Restrições Importantes do Animal</div>
    <div class="info-grid">
        @if($appointment->pet->allergies)
        <div class="info-item" style="grid-column: span 3;">
            <span class="info-label font-bold text-danger">Alergias / Sensibilidade:</span>
            <span class="info-val text-danger font-bold">{{ $appointment->pet->allergies }}</span>
        </div>
        @endif
        @if($appointment->pet->behavior_notes)
        <div class="info-item" style="grid-column: span 3;">
            <span class="info-label font-bold">Comportamento:</span>
            <span class="info-val">{{ $appointment->pet->behavior_notes }}</span>
        </div>
        @endif
        @if($appointment->pet->medical_notes)
        <div class="info-item" style="grid-column: span 3;">
            <span class="info-label">Condições de Saúde:</span>
            <span class="info-val">{{ $appointment->pet->medical_notes }}</span>
        </div>
        @endif
    </div>
</div>
@endif

<div class="section-box">
    <div class="section-title">Serviços Solicitados e Procedimentos</div>
    @if($appointment->services && $appointment->services->count() > 0)
    <table class="table-doc">
        <thead>
            <tr>
                <th>Serviço / Procedimento</th>
                <th style="width: 10%; text-align: center;">Qtd</th>
                <th style="width: 20%; text-align: right;">Valor Unit.</th>
                <th style="width: 20%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appointment->services as $srv)
            <tr>
                <td class="font-bold">{{ $srv->service->name ?? $srv->name ?? 'Serviço de Estética Pet' }}</td>
                <td style="text-align: center;">{{ $srv->quantity }}</td>
                <td style="text-align: right;">R$ {{ number_format($srv->price, 2, ',', '.') }}</td>
                <td style="text-align: right;" class="font-bold">R$ {{ number_format(($srv->price * $srv->quantity) - $srv->discount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8f9fa;">
                <td colspan="3" style="text-align: right;" class="font-bold">Total dos Serviços:</td>
                <td style="text-align: right;" class="font-bold text-primary" style="font-size: 13px;">
                    R$ {{ number_format($appointment->total, 2, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
    @else
    <p style="padding: 6px; font-size: 11px;">
        <strong>Serviço Agendado:</strong> Banho e Cuidados Estéticos Gerais (Tipo: {{ $appointment->reason ?? 'Higienização e Estética Pet' }})
    </p>
    @endif
</div>

<div class="section-box">
    <div class="section-title">Checklist de Entrada e Avaliação Física Prévia</div>
    <table class="table-doc" style="font-size: 10px;">
        <thead>
            <tr>
                <th style="width: 25%;">Item Avaliado</th>
                <th style="width: 25%;">Estado na Recepção</th>
                <th style="width: 25%;">Item Avaliado</th>
                <th style="width: 25%;">Estado na Recepção</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Presença de Nós / Embolado:</strong></td>
                <td>( ) Ausente &nbsp; ( ) Leve &nbsp; ( ) Intenso</td>
                <td><strong>Pulgas / Carrapatos:</strong></td>
                <td>( ) Não &nbsp; ( ) Sim — comunicar tutor</td>
            </tr>
            <tr>
                <td><strong>Pele / Lesões / Feridas:</strong></td>
                <td>( ) Normal &nbsp; ( ) Verificado na entrada</td>
                <td><strong>Ouvidos / Otite:</strong></td>
                <td>( ) Limpo &nbsp; ( ) Secreção / Vermelhidão</td>
            </tr>
            <tr>
                <td><strong>Olhos / Secreção:</strong></td>
                <td>( ) Normais &nbsp; ( ) Secreção</td>
                <td><strong>Unhas / Coxins:</strong></td>
                <td>( ) Normais &nbsp; ( ) Aparar unhas</td>
            </tr>
            <tr>
                <td><strong>Comportamento:</strong></td>
                <td>( ) Dócil &nbsp; ( ) Medroso &nbsp; ( ) Reativo</td>
                <td><strong>Perfume e Acessórios:</strong></td>
                <td>( ) Autorizado &nbsp; ( ) Não usar perfume</td>
            </tr>
        </tbody>
    </table>
</div>

@if($appointment->notes)
<div class="section-box">
    <div class="section-title">Instruções e Observações do Tutor / Recepção</div>
    <div style="font-size: 11px; padding: 6px; line-height: 1.5;">
        {!! nl2br(e($appointment->notes)) !!}
    </div>
</div>
@endif

<div class="section-box">
    <div class="section-title">Termo de Ciência do Tutor & Saída</div>
    <p style="font-size: 9.5px; line-height: 1.4; color: #444; margin: 0 0 8px 0; text-align: justify;">
        Declaro estar ciente dos serviços contratados e das condições físicas do animal relatadas na inspeção de entrada. Autorizo a realização dos procedimentos estéticos e a utilização dos produtos adequados. Caso seja identificado algum problema de saúde que demande intervenção veterinária urgente, autorizo a clínica a prestar o primeiro atendimento.
    </p>
    <div style="display: flex; justify-content: space-between; font-size: 10px; margin-top: 10px;">
        <div><strong>Horário de Saída:</strong> ____:____</div>
        <div><strong>Pertences entregues:</strong> ( ) Coleira / Guia &nbsp; ( ) Roupa &nbsp; ( ) Caixa transporte</div>
        <div><strong>Conferido com o Tutor:</strong> ( ) Sim</div>
    </div>
</div>

<div class="signature-area" style="margin-top: 25px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $appointment->pet->tutors->first()->name ?? 'Tutor / Responsável' }}</span>
            <br>
            <span style="font-size: 10px;">Assinatura na Entrada / Saída</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $appointment->groomer->name ?? $appointment->vet->name ?? 'Profissional Responsável' }}</span>
            <br>
            <span style="font-size: 10px;">Equipe de Banho & Tosa — Amigo Bicho</span>
        </div>
    </div>
</div>
@endsection
