@extends('layouts.print', [
    'title' => 'Relatório de Faturamento e Receitas',
    'subtitle' => 'Período: ' . $startDate->format('d/m/Y') . ' a ' . $endDate->format('d/m/Y') . ' — ' . branding_clinic_name(),
    'docTitle' => 'Relatorio Faturamento ' . $startDate->format('d-m-Y') . ' a ' . $endDate->format('d-m-Y'),
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('reports.export-pdf', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]),
])

@section('content')
<div style="display: flex; gap: 15px; margin-bottom: 12px;">
    <div style="flex: 1; border: 1px solid #132E27; border-radius: 4px; padding: 8px 12px; background: #f8faf9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Período do Relatório</span>
        <div style="font-size: 14px; font-weight: bold; color: #132E27;">{{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}</div>
    </div>
    <div style="flex: 1; border: 1px solid #28a745; border-radius: 4px; padding: 8px 12px; background: #f8fff9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Receita Total Paga</span>
        <div style="font-size: 16px; font-weight: bold; color: #28a745;">R$ {{ number_format($invoices->sum('total'), 2, ',', '.') }}</div>
    </div>
    <div style="flex: 1; border: 1px solid #17a2b8; border-radius: 4px; padding: 8px 12px; background: #f0faff;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Faturas Pagas</span>
        <div style="font-size: 16px; font-weight: bold; color: #17a2b8;">{{ $invoices->count() }} documento(s)</div>
    </div>
    <div style="flex: 1; border: 1px solid #6c757d; border-radius: 4px; padding: 8px 12px; background: #f8f9fa;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Ticket Médio</span>
        <div style="font-size: 14px; font-weight: bold; color: #333;">
            R$ {{ $invoices->count() > 0 ? number_format($invoices->sum('total') / $invoices->count(), 2, ',', '.') : '0,00' }}
        </div>
    </div>
</div>

<table class="table-doc">
    <thead>
        <tr>
            <th style="width: 14%;">Fatura Nº</th>
            <th style="width: 26%;">Tutor / Pagador</th>
            <th style="width: 18%;">Paciente / Pet</th>
            <th style="width: 14%; text-align: center;">Data Pagamento</th>
            <th style="width: 14%; text-align: center;">Forma de Pgto</th>
            <th style="width: 14%; text-align: right;">Valor Pago</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $inv)
        <tr>
            <td class="font-bold">{{ $inv->invoice_number ?? $inv->id }}</td>
            <td>
                <strong>{{ $inv->tutor->name ?? 'Não identificado' }}</strong>
                @if($inv->tutor && $inv->tutor->phone)
                    <br><small class="text-muted">{{ $inv->tutor->phone }}</small>
                @endif
            </td>
            <td>{{ $inv->pet->name ?? '-' }}</td>
            <td style="text-align: center;">{{ $inv->paid_at ? $inv->paid_at->format('d/m/Y H:i') : '-' }}</td>
            <td style="text-align: center;">
                @php
                    $methodMap = [
                        'pix' => 'PIX',
                        'credit_card' => 'Cartão de Crédito',
                        'debit_card' => 'Cartão de Débito',
                        'cash' => 'Dinheiro',
                        'bank_slip' => 'Boleto',
                    ];
                @endphp
                <span class="badge badge-info">{{ $methodMap[$inv->payment_method] ?? ($inv->payment_method ? ucfirst($inv->payment_method) : '-') }}</span>
            </td>
            <td style="text-align: right;" class="font-bold text-success">
                R$ {{ number_format($inv->total, 2, ',', '.') }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 25px; color: #666;">
                Nenhuma fatura paga encontrada no período selecionado ({{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}).
            </td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background: #f8f9fa;">
            <td colspan="5" style="text-align: right;" class="font-bold">Total Recebido no Período:</td>
            <td style="text-align: right;" class="font-bold text-success" style="font-size: 13px;">
                R$ {{ number_format($invoices->sum('total'), 2, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>

<div class="signature-area" style="margin-top: 30px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Coordenação Financeira</span>
            <br>
            <span style="font-size: 10px;">{{ branding_clinic_name() }}</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Diretoria Administrativa</span>
            <br>
            <span style="font-size: 10px;">Visto & Homologação</span>
        </div>
    </div>
</div>
@endsection
