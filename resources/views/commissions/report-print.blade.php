@extends('layouts.print')

@php $pageOrientation = 'landscape'; @endphp

@section('content')
<div class="section-box">
    <div class="section-title">Relatório de Comissões
        @if(isset($dateFrom) || isset($dateTo))
        — Período:
        {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Início' }}
        a
        {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Hoje' }}
        @endif
    </div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total de Registros:</span><br>
                    <span class="info-value font-bold">{{ $logs->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total Base (Vendas):</span><br>
                    <span class="info-value font-bold">R$ {{ number_format($totals->total_base ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total de Comissões:</span><br>
                    <span class="info-value font-bold" style="color:#1a7f3c;">R$ {{ number_format($totals->total_commission ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Detalhamento por Profissional</div>
    <div class="section-body">
        @php
            $byUser = $logs->groupBy(fn($l) => $l->user->name ?? 'Sem usuário');
        @endphp
        @foreach($byUser as $userName => $userLogs)
        <div style="margin-bottom: 14px;">
            <div style="font-weight:bold; background:#f4f5f7; padding:5px 8px; border-radius:3px; margin-bottom:6px;">
                👤 {{ $userName }}
                — Comissão Total: <span style="color:#1a7f3c;">R$ {{ number_format($userLogs->sum('commission_value'), 2, ',', '.') }}</span>
            </div>
            <table class="table-doc">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Fatura</th>
                        <th>Descrição</th>
                        <th class="text-right">Valor Base</th>
                        <th class="text-right">Taxa</th>
                        <th class="text-right">Comissão</th>
                        <th>Status</th>
                        <th>Pago em</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($userLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y') }}</td>
                        <td>#{{ $log->invoice_id ?? '-' }}</td>
                        <td>{{ $log->description ?? '-' }}</td>
                        <td class="text-right">R$ {{ number_format($log->base_value ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">{{ $log->commission_rate ?? '-' }}%</td>
                        <td class="text-right font-bold">R$ {{ number_format($log->commission_value ?? 0, 2, ',', '.') }}</td>
                        <td>
                            @if($log->status === 'paid') <span class="badge badge-success">Pago</span>
                            @else <span class="badge badge-warning">Pendente</span> @endif
                        </td>
                        <td>{{ $log->paid_at ? \Carbon\Carbon::parse($log->paid_at)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</div>
@endsection
