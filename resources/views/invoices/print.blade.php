@extends('layouts.print', [
    'title' => ($invoice->status === 'paid' ? 'Recibo de Pagamento ' : 'Fatura de Serviços ') . $invoice->invoice_number,
    'subtitle' => ($invoice->status === 'paid' ? 'Comprovante de Quitação ' : 'Demonstrativo de Conta ') . '— ' . branding_clinic_name(),
    'docTitle' => ($invoice->status === 'paid' ? 'Recibo ' : 'Fatura ') . str_replace('/', '-', $invoice->invoice_number),
    'pdfUrl' => $pdfUrl ?? route('invoices.pdf', $invoice),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados da Fatura & Quitação</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Documento Nº:</span>
            <span class="info-val font-bold" style="font-size: 13px;">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Emissão:</span>
            <span class="info-val font-bold">{{ $invoice->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status Financeiro:</span>
            <span class="info-val">
                @php
                    $statusBadge = match($invoice->status) {
                        'paid' => 'badge-success',
                        'pending' => 'badge-warning',
                        'overdue' => 'badge-danger',
                        default => 'badge-secondary',
                    };
                    $statusLabel = match($invoice->status) {
                        'paid' => 'PAGO / QUITADO',
                        'pending' => 'PENDENTE DE PAGAMENTO',
                        'overdue' => 'VENCIDO',
                        'cancelled' => 'CANCELADO',
                        default => strtoupper($invoice->status),
                    };
                @endphp
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Data de Vencimento:</span>
            <span class="info-val font-bold {{ $invoice->status === 'overdue' ? 'text-danger' : '' }}">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Quitação:</span>
            <span class="info-val font-bold text-success">{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : ($invoice->status === 'paid' ? $invoice->updated_at->format('d/m/Y') : 'Não quitado') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Forma de Pagamento:</span>
            <span class="info-val">
                @php
                    $methodMap = [
                        'pix' => 'PIX',
                        'credit_card' => 'Cartão de Crédito',
                        'debit_card' => 'Cartão de Débito',
                        'cash' => 'Dinheiro',
                        'bank_slip' => 'Boleto Bancário',
                        'transfer' => 'Transferência',
                    ];
                @endphp
                {{ $methodMap[$invoice->payment_method] ?? ($invoice->payment_method ? ucfirst($invoice->payment_method) : '-') }}
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Tutor / Pagador:</span>
            <span class="info-val font-bold" style="font-size: 12px;">{{ $invoice->tutor->name ?? 'Não identificado' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">CPF / CNPJ:</span>
            <span class="info-val">{{ $invoice->tutor->cpf ?? $invoice->tutor->document ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone:</span>
            <span class="info-val">{{ $invoice->tutor->phone ?? '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Paciente / Pet:</span>
            <span class="info-val font-bold">{{ $invoice->pet->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Espécie / Raça:</span>
            <span class="info-val">{{ $invoice->pet->species ?? '-' }} / {{ $invoice->pet->breed ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Unidade / Empresa:</span>
            <span class="info-val">{{ branding_clinic_name() }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Discriminação dos Serviços e Produtos</div>
    <table class="table-doc">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">Item</th>
                <th style="width: 50%;">Descrição do Item / Procedimento</th>
                <th style="width: 10%; text-align: center;">Qtd</th>
                <th style="width: 15%; text-align: right;">Preço Unit.</th>
                <th style="width: 20%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td class="font-bold">{!! $item->description !!}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td style="text-align: right;" class="font-bold">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 25px; color: #666;">
                    Nenhum item discriminado nesta fatura.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            @if($invoice->discount && $invoice->discount > 0)
            <tr>
                <td colspan="4" style="text-align: right;" class="text-muted">Subtotal Bruto:</td>
                <td style="text-align: right;" class="text-muted">R$ {{ number_format($invoice->subtotal ?? ($invoice->total + $invoice->discount), 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right;" class="text-success">Desconto Concedido:</td>
                <td style="text-align: right;" class="text-success font-bold">- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="background: #f8f9fa;">
                <td colspan="4" style="text-align: right;" class="font-bold" style="font-size: 12px;">Valor Total Líquido:</td>
                <td style="text-align: right;" class="font-bold text-primary" style="font-size: 14px;">
                    R$ {{ number_format($invoice->total, 2, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>

@if($invoice->status === 'paid')
<div class="section-box" style="border: 2px solid #28a745; background: #f0fff4;">
    <div class="section-title" style="color: #1e7e34; border-bottom-color: #28a745;">Declaração de Quitação</div>
    <p style="font-size: 11px; line-height: 1.5; color: #155724; margin: 0; padding: 4px;">
        Recebemos de <strong>{{ $invoice->tutor->name ?? 'Tutor(a)' }}</strong> a quantia de <strong>R$ {{ number_format($invoice->total, 2, ',', '.') }}</strong> referente aos serviços veterinários e/ou produtos descritos acima, dando-lhe por este documento plena, rasa e irrevogável quitação.
    </p>
</div>
@endif

@if($invoice->notes)
<div class="section-box">
    <div class="section-title">Observações Complementares</div>
    <div style="font-size: 11px; padding: 6px; line-height: 1.5;">
        {!! nl2br(e($invoice->notes)) !!}
    </div>
</div>
@endif

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $invoice->tutor->name ?? 'Cliente / Tutor' }}</span>
            <br>
            <span style="font-size: 10px;">Assinatura do Contratante</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ branding_clinic_name() }}</span>
            <br>
            <span style="font-size: 10px;">Departamento Financeiro / Recepção</span>
        </div>
    </div>
</div>
@endsection
