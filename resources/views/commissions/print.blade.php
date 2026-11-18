@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Comprovante de Comissão #{{ $commissionLog->id }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Profissional / Veterinário:</span><br>
                    <span class="info-value font-bold">{{ $commissionLog->user->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Data de Registro:</span><br>
                    <span class="info-value">{{ $commissionLog->created_at ? $commissionLog->created_at->format('d/m/Y H:i') : '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Fatura Vinculada:</span><br>
                    <span class="info-value">#{{ $commissionLog->invoice->invoice_number ?? $commissionLog->invoice_id ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Status do Pagamento:</span><br>
                    <span class="info-value">
                        @if($commissionLog->status === 'paid')
                            <span class="badge badge-success">Pago em {{ $commissionLog->paid_at ? \Carbon\Carbon::parse($commissionLog->paid_at)->format('d/m/Y') : '-' }}</span>
                        @else
                            <span class="badge badge-warning">Pendente</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Valores e Taxas</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Valor Base:</span><br>
                    <span class="info-value">R$ {{ number_format($commissionLog->base_value ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Taxa Aplicada:</span><br>
                    <span class="info-value">
                        @if($commissionLog->commissionRate)
                            {{ $commissionLog->commissionRate->rate_type === 'percentage' ? $commissionLog->commissionRate->rate_value . '%' : 'R$ ' . number_format($commissionLog->commissionRate->rate_value, 2, ',', '.') }}
                        @elseif($commissionLog->commission_rate)
                            {{ $commissionLog->commission_rate }}%
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Valor da Comissão:</span><br>
                    <span class="info-value font-bold" style="color:#1a7f3c; font-size:13px;">R$ {{ number_format($commissionLog->commission_value ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
            @if($commissionLog->description)
            <div class="info-row">
                <div class="info-cell" style="width:100%" colspan="3">
                    <span class="info-label">Descrição:</span><br>
                    <span class="info-value">{{ $commissionLog->description }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $commissionLog->user->name ?? 'Profissional' }}</div>
                <div class="signature-sub">Assinatura do Beneficiário</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">Amigo Bicho</div>
                <div class="signature-sub">Administração / Financeiro</div>
            </td>
        </tr>
    </table>
</div>
@endsection
