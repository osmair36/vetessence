@extends('layouts.print', [
    'title' => 'Pedido de Compra ' . $purchaseOrder->order_number,
    'subtitle' => 'Ordem de Compra / Fornecimento — ' . branding_clinic_name(),
    'docTitle' => 'Pedido de Compra ' . str_replace(['/', '\\'], '-', $purchaseOrder->order_number),
    'pdfUrl' => $pdfUrl ?? route('purchase-orders.pdf', $purchaseOrder),
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados do Pedido e Fornecedor</div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Pedido Nº:</span>
            <span class="info-val font-bold" style="font-size: 13px;">{{ $purchaseOrder->order_number }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Emissão:</span>
            <span class="info-val font-bold">{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Status do Pedido:</span>
            <span class="info-val">
                @php
                    $statusBadge = match($purchaseOrder->status) {
                        'draft' => 'badge-secondary',
                        'ordered' => 'badge-info',
                        'received' => 'badge-success',
                        default => 'badge-warning',
                    };
                    $statusLabel = match($purchaseOrder->status) {
                        'draft' => 'RASCUNHO / EM COTAÇÃO',
                        'ordered' => 'ENVIADO AO FORNECEDOR',
                        'received' => 'RECEBIDO NO ESTOQUE',
                        default => strtoupper($purchaseOrder->status),
                    };
                @endphp
                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
            </span>
        </div>

        <div class="info-item">
            <span class="info-label">Fornecedor:</span>
            <span class="info-val font-bold" style="font-size: 12px;">{{ $purchaseOrder->supplier->name ?? 'Não definido' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">CNPJ / CPF Fornecedor:</span>
            <span class="info-val">{{ $purchaseOrder->supplier->document ?? $purchaseOrder->supplier->cnpj ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Telefone / E-mail:</span>
            <span class="info-val">{{ $purchaseOrder->supplier->phone ?? '-' }} | {{ $purchaseOrder->supplier->email ?? '-' }}</span>
        </div>

        <div class="info-item">
            <span class="info-label">Solicitante:</span>
            <span class="info-val">{{ $purchaseOrder->requester->name ?? 'Usuário do Sistema' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Aprovador:</span>
            <span class="info-val">{{ $purchaseOrder->approver->name ?? '-' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Data de Recebimento:</span>
            <span class="info-val">{{ $purchaseOrder->received_at ? $purchaseOrder->received_at->format('d/m/Y H:i') : 'Pendente' }}</span>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Itens do Pedido & Guia de Conferência Física</div>
    <table class="table-doc">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">Item</th>
                <th style="width: 12%;">SKU / Cód.</th>
                <th style="width: 33%;">Descrição do Produto</th>
                <th style="width: 10%; text-align: center;">Qtd Pedida</th>
                <th style="width: 13%; text-align: right;">Preço Unit.</th>
                <th style="width: 13%; text-align: right;">Subtotal</th>
                <th style="width: 14%; text-align: center;">Conferência Física</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchaseOrder->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td class="font-bold">{{ $item->product->sku ?? '-' }}</td>
                <td>
                    <span class="font-bold">{{ $item->product->name ?? 'Produto #' . $item->product_id }}</span>
                    @if($item->product && $item->product->unit)
                        <small class="text-muted">({{ $item->product->unit }})</small>
                    @endif
                </td>
                <td style="text-align: center;" class="font-bold">{{ $item->quantity }}</td>
                <td style="text-align: right;">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td style="text-align: right;" class="font-bold">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                <td style="text-align: center; border-left: 2px dashed #999;">
                    @if($purchaseOrder->status === 'received')
                        <span class="badge badge-success">Rec: {{ $item->quantity_received ?? $item->quantity }}</span>
                    @else
                        [ &nbsp; &nbsp; ] Qtd: _____
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 25px; color: #666;">
                    Nenhum item adicionado a este pedido de compra.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f8f9fa;">
                <td colspan="5" style="text-align: right;" class="font-bold">Valor Total do Pedido:</td>
                <td style="text-align: right;" class="font-bold text-primary" style="font-size: 13px;">
                    R$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@if($purchaseOrder->notes)
<div class="section-box">
    <div class="section-title">Instruções de Faturamento e Entrega</div>
    <div style="font-size: 11px; padding: 6px; line-height: 1.5;">
        {!! nl2br(e($purchaseOrder->notes)) !!}
    </div>
</div>
@endif

<div class="section-box">
    <div class="section-title">Condições de Fornecimento</div>
    <p style="font-size: 9.5px; line-height: 1.4; color: #444; margin: 0; text-align: justify;">
        Os produtos devem ser entregues no endereço cadastrado da empresa, acompanhados da respectiva Nota Fiscal Eletrônica (NF-e). Favor verificar o prazo de validade mínimo de 12 meses na data de entrega. Mercadorias com embalagens danificadas ou divergências de lote/quantidade serão recusadas no ato da conferência.
    </p>
</div>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">{{ $purchaseOrder->requester->name ?? 'Responsável pela Compra' }}</span>
            <br>
            <span style="font-size: 10px;">Emissão / Aprovação do Pedido</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Conferente / Almoxarifado</span>
            <br>
            <span style="font-size: 10px;">Assinatura no Recebimento Físico</span>
        </div>
    </div>
</div>
@endsection
