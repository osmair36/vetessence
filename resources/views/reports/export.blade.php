@extends('layouts.adminlte', ['title' => 'Exportar Relatório'])

@section('content')
<div class="card">
    <div class="card-header d-print-none">
        <h3 class="card-title">Relatório de Faturamento</h3>
        <div class="card-tools">
            <button type="button" onclick="window.print()" class="btn btn-default btn-sm"><i class="fas fa-print"></i> Imprimir</button>
        </div>
    </div>
    <div class="card-body">
        <x-report-header title="Relatório de Faturamento" subtitle="Período: {{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}" />

        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Fatura</th>
                        <th>Tutor</th>
                        <th>Data Pagamento</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>{{ $inv->invoice_number ?? $inv->id }}</td>
                        <td>{{ $inv->tutor->name ?? '-' }}</td>
                        <td>{{ $inv->paid_at ? $inv->paid_at->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">R$ {{ number_format($inv->total, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">Nenhuma fatura encontrada no período selecionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 font-weight-bold">Total de faturas pagas: {{ $invoices->count() }} | Soma Total: R$ {{ number_format($invoices->sum('total'), 2, ',', '.') }}</p>
    </div>
</div>
@endsection
