@extends('layouts.print', [
    'title' => 'Extrato de Movimentações de Estoque',
    'subtitle' => 'Histórico de Entradas, Saídas e Ajustes — ' . $movements->count() . ' registro(s) — ' . branding_clinic_name(),
    'docTitle' => 'Movimentacoes de Estoque ' . date('d-m-Y'),
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('stock.movements.pdf', request()->query()),
])

@section('content')
<div style="display: flex; gap: 15px; margin-bottom: 12px;">
    <div style="flex: 1; border: 1px solid #132E27; border-radius: 4px; padding: 8px 12px; background: #f8faf9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Registros</span>
        <div style="font-size: 16px; font-weight: bold; color: #132E27;">{{ $movements->count() }} movimentações</div>
    </div>
    <div style="flex: 1; border: 1px solid #28a745; border-radius: 4px; padding: 8px 12px; background: #f8fff9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Entradas</span>
        <div style="font-size: 16px; font-weight: bold; color: #28a745;">+{{ $movements->whereIn('type', ['in', 'purchase', 'transfer_in'])->sum('quantity') }} itens</div>
    </div>
    <div style="flex: 1; border: 1px solid #dc3545; border-radius: 4px; padding: 8px 12px; background: #fff8f8;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Saídas</span>
        <div style="font-size: 16px; font-weight: bold; color: #dc3545;">-{{ $movements->whereIn('type', ['out', 'sale', 'transfer_out', 'loss'])->sum('quantity') }} itens</div>
    </div>
    <div style="flex: 1; border: 1px solid #6c757d; border-radius: 4px; padding: 8px 12px; background: #f8f9fa;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Período / Emissão</span>
        <div style="font-size: 13px; font-weight: bold; color: #333;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<table class="table-doc">
    <thead>
        <tr>
            <th style="width: 12%;">Data / Hora</th>
            <th style="width: 28%;">Produto</th>
            <th style="width: 12%; text-align: center;">Tipo</th>
            <th style="width: 8%; text-align: center;">Qtd</th>
            <th style="width: 15%;">Usuário / Resp.</th>
            <th style="width: 25%;">Motivo / Observações</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $m)
        <tr>
            <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
            <td class="font-bold">
                {{ $m->product->name ?? 'Produto #' . $m->product_id }}
                @if($m->product && $m->product->sku)
                    <small class="text-muted">({{ $m->product->sku }})</small>
                @endif
            </td>
            <td style="text-align: center;">
                @php
                    $typeLabels = [
                        'in' => 'Entrada',
                        'out' => 'Saída',
                        'adjustment' => 'Ajuste',
                        'transfer_in' => 'Transf. Entrada',
                        'transfer_out' => 'Transf. Saída',
                        'sale' => 'Venda / PDV',
                        'purchase' => 'Compra',
                        'loss' => 'Perda / Avaria',
                    ];
                    $badgeClass = match($m->type) {
                        'in', 'purchase', 'transfer_in' => 'badge-success',
                        'out', 'sale', 'transfer_out', 'loss' => 'badge-danger',
                        default => 'badge-info',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $typeLabels[$m->type] ?? ucfirst($m->type) }}</span>
            </td>
            <td style="text-align: center;" class="font-bold {{ in_array($m->type, ['in', 'purchase', 'transfer_in']) ? 'text-success' : 'text-danger' }}">
                {{ in_array($m->type, ['in', 'purchase', 'transfer_in']) ? '+' : '-' }}{{ $m->quantity }}
            </td>
            <td>{{ $m->user->name ?? 'Sistema' }}</td>
            <td>{{ $m->notes ?: ($m->reason ?: '-') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 25px; color: #666;">
                Nenhuma movimentação de estoque encontrada para os filtros selecionados.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Emissor do Relatório</span>
            <br>
            <span style="font-size: 10px;">{{ auth()->user()->name ?? 'Usuário do Sistema' }}</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Gestão de Materiais e Farmácia</span>
            <br>
            <span style="font-size: 10px;">Amigo Bicho — Controle Interno</span>
        </div>
    </div>
</div>
@endsection
