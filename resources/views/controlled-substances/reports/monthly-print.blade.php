@extends('layouts.print', [
    'title' => 'Livro de Registro e Mapa Mensal de Substâncias Controladas (Portaria 344/98)',
    'subtitle' => 'Mês de Referência: ' . $month . ' — Resp. Técnica: Dra. Joelma Scarabelli Neves (CRMV-PR 08147) — ' . branding_clinic_name(),
    'docTitle' => 'Livro Controlados ' . $month,
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('controlled-substances.reports.monthly.pdf', ['month' => $month]),
])

@section('content')
<div style="display: flex; gap: 15px; margin-bottom: 12px;">
    <div style="flex: 1; border: 1px solid #132E27; border-radius: 4px; padding: 8px 12px; background: #f8faf9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Período do Mapa</span>
        <div style="font-size: 15px; font-weight: bold; color: #132E27;">Mês: {{ $month }}</div>
    </div>
    <div style="flex: 1; border: 1px solid #28a745; border-radius: 4px; padding: 8px 12px; background: #f8fff9;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Entradas</span>
        <div style="font-size: 15px; font-weight: bold; color: #28a745;">+{{ $logs->where('type', 'entry')->sum('quantity') }} un/amp</div>
    </div>
    <div style="flex: 1; border: 1px solid #dc3545; border-radius: 4px; padding: 8px 12px; background: #fff8f8;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Saídas / Aplicações</span>
        <div style="font-size: 15px; font-weight: bold; color: #dc3545;">-{{ $logs->where('type', 'exit')->sum('quantity') }} un/amp</div>
    </div>
    <div style="flex: 1; border: 1px solid #6c757d; border-radius: 4px; padding: 8px 12px; background: #f8f9fa;">
        <span style="font-size: 10px; color: #555; text-transform: uppercase;">Total de Movimentações</span>
        <div style="font-size: 15px; font-weight: bold; color: #333;">{{ $logs->count() }} registro(s)</div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Resumo por Substância Controlada</div>
    <table class="table-doc" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width: 35%;">Substância / Princípio Ativo</th>
                <th style="width: 15%; text-align: center;">Lista / Portaria</th>
                <th style="width: 15%; text-align: center;">Entradas</th>
                <th style="width: 15%; text-align: center;">Saídas</th>
                <th style="width: 20%; text-align: center;">Saldo Final</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grouped as $name => $items)
            @php
                $entries = $items->where('type', 'entry')->sum('quantity');
                $exits = $items->where('type', 'exit')->sum('quantity');
                $substance = $items->first()->substance ?? null;
            @endphp
            <tr>
                <td class="font-bold">{{ $name }}</td>
                <td style="text-align: center;"><span class="badge badge-info">{{ $substance->list_category ?? 'Portaria 344' }}</span></td>
                <td style="text-align: center;" class="text-success font-bold">+{{ $entries }}</td>
                <td style="text-align: center;" class="text-danger font-bold">-{{ $exits }}</td>
                <td style="text-align: center;" class="font-bold">{{ $substance->current_stock ?? ($entries - $exits) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #666; padding: 15px;">
                    Nenhuma substância movimentada no mês selecionado.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="section-box">
    <div class="section-title">Livro Registro de Movimentações Cronológicas (Entradas e Saídas)</div>
    <table class="table-doc">
        <thead>
            <tr>
                <th style="width: 11%;">Data / Hora</th>
                <th style="width: 22%;">Medicamento / Substância</th>
                <th style="width: 8%; text-align: center;">Tipo</th>
                <th style="width: 8%; text-align: center;">Qtd</th>
                <th style="width: 12%;">Lote / Validade</th>
                <th style="width: 21%;">Destino / Paciente / Fornecedor</th>
                <th style="width: 18%;">Veterinário / Resp.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td class="font-bold">{{ $log->substance->name ?? 'Substância' }}</td>
                <td style="text-align: center;">
                    <span class="badge {{ $log->type === 'entry' ? 'badge-success' : 'badge-danger' }}">
                        {{ $log->type === 'entry' ? 'ENTRADA' : 'SAÍDA' }}
                    </span>
                </td>
                <td style="text-align: center;" class="font-bold {{ $log->type === 'entry' ? 'text-success' : 'text-danger' }}">
                    {{ $log->type === 'entry' ? '+' : '-' }}{{ $log->quantity }}
                </td>
                <td>{{ $log->batch_number ?: ($log->lot_number ?: '-') }}</td>
                <td>
                    @if($log->prescription)
                        Prescrição #{{ $log->prescription->id }}
                    @elseif($log->patient_name || $log->observations)
                        {{ $log->patient_name ?: $log->observations }}
                    @else
                        {{ $log->supplier_name ?: 'Movimento interno' }}
                    @endif
                </td>
                <td>{{ $log->user->name ?? 'Dra. Joelma Scarabelli Neves' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 25px; color: #666;">
                    Nenhum registro cronológico encontrado para este mês.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="signature-area" style="margin-top: 35px;">
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Dra. Joelma Scarabelli Neves</span>
            <br>
            <span class="text-muted" style="font-size: 10px;">Médica Veterinária — CRMV-PR 08147</span>
            <br>
            <span style="font-size: 10px;">Responsável Técnica pelo Estabelecimento</span>
        </div>
    </div>
    <div class="signature-col">
        <div class="signature-line">
            <span class="font-bold">Vigilância Sanitária / MAPA</span>
            <br>
            <span style="font-size: 10px;">Visto da Autoridade Sanitária Fiscalizadora</span>
        </div>
    </div>
</div>
@endsection
