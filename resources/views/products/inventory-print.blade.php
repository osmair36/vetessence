@extends('layouts.print', [
    'title' => 'Posição Geral de Estoque e Inventário Físico',
    'subtitle' => 'Inventário de Produtos — Total de ' . $products->count() . ' item(ns) cadastrados — ' . branding_clinic_name(),
    'docTitle' => 'Inventario de Estoque ' . date('d-m-Y'),
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('products.inventory.pdf', request()->query()),
])

@section('content')
<div style="display: flex; gap: 15px; margin-bottom: 12px;">
    <div style="flex: 1; border: 1px solid #132E27; border-radius: 4px; padding: 8px 12px; background: #f8faf9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Itens</span>
        <div style="font-size: 16px; font-weight: bold; color: #132E27;">{{ $products->count() }} produtos</div>
    </div>
    <div style="flex: 1; border: 1px solid #28a745; border-radius: 4px; padding: 8px 12px; background: #f8fff9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Valor Total em Estoque (Custo)</span>
        <div style="font-size: 16px; font-weight: bold; color: #28a745;">R$ {{ number_format($products->sum(fn($p) => $p->stock * ($p->cost_price ?? 0)), 2, ',', '.') }}</div>
    </div>
    <div style="flex: 1; border: 1px solid #dc3545; border-radius: 4px; padding: 8px 12px; background: #fff8f8;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Itens com Estoque Baixo</span>
        <div style="font-size: 16px; font-weight: bold; color: #dc3545;">{{ $products->filter(fn($p) => $p->min_stock && $p->stock <= $p->min_stock)->count() }} item(ns)</div>
    </div>
    <div style="flex: 1; border: 1px solid #6c757d; border-radius: 4px; padding: 8px 12px; background: #f8f9fa;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Data de Emissão</span>
        <div style="font-size: 14px; font-weight: bold; color: #333;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<table class="table-doc">
    <thead>
        <tr>
            <th style="width: 8%;">SKU / Cód.</th>
            <th style="width: 25%;">Descrição do Produto</th>
            <th style="width: 13%;">Categoria</th>
            <th style="width: 14%;">Fornecedor</th>
            <th style="width: 7%; text-align: center;">Mín.</th>
            <th style="width: 8%; text-align: center;">Estoque</th>
            <th style="width: 9%; text-align: right;">Custo Unit.</th>
            <th style="width: 9%; text-align: right;">Preço Venda</th>
            <th style="width: 7%; text-align: center;">Contagem</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr>
            <td class="font-bold">{{ $p->sku ?: '-' }}</td>
            <td>
                <span class="font-bold">{{ $p->name }}</span>
                @if($p->unit)
                    <small class="text-muted">({{ $p->unit }})</small>
                @endif
                @if($p->min_stock && $p->stock <= $p->min_stock)
                    <span class="badge badge-danger" style="font-size: 8px;">BAIXO</span>
                @endif
            </td>
            <td>{{ $p->category->name ?? '-' }}</td>
            <td>{{ $p->supplier->name ?? '-' }}</td>
            <td style="text-align: center;">{{ $p->min_stock ?? 0 }}</td>
            <td style="text-align: center;" class="font-bold {{ ($p->min_stock && $p->stock <= $p->min_stock) ? 'text-danger' : '' }}">
                {{ $p->stock ?? 0 }}
            </td>
            <td style="text-align: right;">R$ {{ number_format($p->cost_price ?? 0, 2, ',', '.') }}</td>
            <td style="text-align: right;" class="font-bold">R$ {{ number_format($p->price ?? 0, 2, ',', '.') }}</td>
            <td style="text-align: center; border-left: 2px dashed #999;">
                [ &nbsp; &nbsp; &nbsp; ]
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center; padding: 25px; color: #666;">
                Nenhum produto cadastrado no estoque.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Responsável pela Contagem / Conferência</span>
            <br>
            <span style="font-size: 10px;">Assinatura & Data</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Gerência / Controle de Estoque</span>
            <br>
            <span style="font-size: 10px;">Amigo Bicho — Almoxarifado</span>
        </div>
    </div>
</div>
@endsection
