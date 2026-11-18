@extends('layouts.print', [
    'title' => 'Relatório de Produtos Próximos ao Vencimento / Vencidos',
    'subtitle' => 'Produtos com validade nos próximos ' . $days . ' dias — Total: ' . $products->count() . ' item(ns) — ' . branding_clinic_name(),
    'docTitle' => 'Lotes a Vencer ' . date('d-m-Y'),
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('stock.expiring.pdf', ['days' => $days]),
])

@section('content')
<div style="display: flex; gap: 15px; margin-bottom: 12px;">
    <div style="flex: 1; border: 1px solid #132E27; border-radius: 4px; padding: 8px 12px; background: #f8faf9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Produtos em Alerta</span>
        <div style="font-size: 16px; font-weight: bold; color: #132E27;">{{ $products->count() }} itens</div>
    </div>
    <div style="flex: 1; border: 1px solid #dc3545; border-radius: 4px; padding: 8px 12px; background: #fff8f8;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Produtos Já Vencidos</span>
        <div style="font-size: 16px; font-weight: bold; color: #dc3545;">
            {{ $products->filter(fn($p) => $p->expiration_date && $p->expiration_date->isPast())->count() }} item(ns)
        </div>
    </div>
    <div style="flex: 1; border: 1px solid #ffc107; border-radius: 4px; padding: 8px 12px; background: #fffdf0;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Janela de Monitoramento</span>
        <div style="font-size: 16px; font-weight: bold; color: #d39e00;">Próximos {{ $days }} dias</div>
    </div>
    <div style="flex: 1; border: 1px solid #6c757d; border-radius: 4px; padding: 8px 12px; background: #f8f9fa;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Data de Emissão</span>
        <div style="font-size: 13px; font-weight: bold; color: #333;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<table class="table-doc">
    <thead>
        <tr>
            <th style="width: 10%;">SKU / Cód.</th>
            <th style="width: 30%;">Descrição do Produto</th>
            <th style="width: 15%;">Lote</th>
            <th style="width: 12%; text-align: center;">Data Validade</th>
            <th style="width: 11%; text-align: center;">Dias Restantes</th>
            <th style="width: 10%; text-align: center;">Estoque</th>
            <th style="width: 12%; text-align: right;">Custo Estimado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        @php
            $isExpired = $p->expiration_date && $p->expiration_date->isPast();
            $daysLeft = $p->expiration_date ? (int) now()->diffInDays($p->expiration_date, false) : null;
        @endphp
        <tr>
            <td class="font-bold">{{ $p->sku ?: '-' }}</td>
            <td>
                <span class="font-bold">{{ $p->name }}</span>
                @if($p->category)
                    <br><small class="text-muted">{{ $p->category->name }}</small>
                @endif
            </td>
            <td>{{ $p->lot_number ?: ($p->batch ?: 'Padrão') }}</td>
            <td style="text-align: center;" class="font-bold {{ $isExpired ? 'text-danger' : '' }}">
                {{ $p->expiration_date ? $p->expiration_date->format('d/m/Y') : '-' }}
            </td>
            <td style="text-align: center;">
                @if($isExpired)
                    <span class="badge badge-danger">VENCIDO ({{ abs($daysLeft) }}d atrás)</span>
                @elseif($daysLeft <= 15)
                    <span class="badge badge-danger">{{ $daysLeft }} dia(s)</span>
                @else
                    <span class="badge badge-warning">{{ $daysLeft }} dia(s)</span>
                @endif
            </td>
            <td style="text-align: center;" class="font-bold">
                {{ $p->stock }} {{ $p->unit ?? 'un' }}
            </td>
            <td style="text-align: right;">
                R$ {{ number_format(($p->stock ?? 0) * ($p->cost_price ?? 0), 2, ',', '.') }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 25px; color: #666;">
                Nenhum produto vencendo nos próximos {{ $days }} dias.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Farmacêutico(a) / Resp. Técnico</span>
            <br>
            <span style="font-size: 10px;">Dra. Joelma Scarabelli Neves — CRMV-PR 08147</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Coordenação de Almoxarifado</span>
            <br>
            <span style="font-size: 10px;">Amigo Bicho — Gestão de Validades</span>
        </div>
    </div>
</div>
@endsection
