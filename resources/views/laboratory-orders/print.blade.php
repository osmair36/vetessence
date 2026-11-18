@extends('layouts.print', [
    'title' => 'Laudo de Exames Laboratoriais',
    'subtitle' => 'Pedido ' . $order->order_number . ' — ' . ($order->result_date ? 'Resultado em: ' . $order->result_date->format('d/m/Y') : 'Coleta em: ' . ($order->order_date ? $order->order_date->format('d/m/Y') : '-')),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Laudo Laboratorial - ' . $order->order_number,
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Dados do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $order->pet->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($order->pet->species ?? '-') }} / {{ $order->pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $order->pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $order->pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Microchip:</span> <br>{{ $order->pet->microchip ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor:</span> <br><strong>{{ $order->pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Telefone:</span> <br>{{ $order->pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Veterinário Solicitante:</span> <br>{{ $order->vet->name ?? 'Corpo Clínico' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Dados da Solicitação Laboratorial</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Nº Pedido:</span> <br><strong>{{ $order->order_number }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Data da Coleta:</span> <br>{{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Data do Resultado:</span> <br>{{ $order->result_date ? $order->result_date->format('d/m/Y') : 'Pendente' }}</td>
                <td style="width: 25%;"><span class="info-label">Laboratório:</span> <br>{{ $order->lab_name ?? 'Laboratório Próprio Amigo Bicho' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">3. Resultados dos Exames e Análises Clínicas</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 30%;">Exame / Parâmetro</th>
                    <th style="width: 20%;">Resultado</th>
                    <th style="width: 15%;">Unidade</th>
                    <th style="width: 20%;">Valores de Referência</th>
                    <th style="width: 15%;">Situação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->tests as $test)
                <tr>
                    <td><strong>{{ $test->test_name }}</strong> @if($test->test_code)<small style="color: #666;">({{ $test->test_code }})</small>@endif</td>
                    <td>
                        <strong style="{{ $test->is_abnormal ? 'color: #c82333; font-size: 11px;' : '' }}">
                            {{ $test->result ?: 'Aguardando resultado' }}
                        </strong>
                    </td>
                    <td>{{ $test->unit ?? '-' }}</td>
                    <td>{{ $test->reference_range ?? '-' }}</td>
                    <td>
                        @if($test->is_abnormal)
                            <span class="badge badge-danger">ALTERADO</span>
                        @elseif($test->result)
                            <span class="badge badge-success">NORMAL</span>
                        @else
                            <span class="badge badge-warning">PENDENTE</span>
                        @endif
                    </td>
                </tr>
                @if($test->observations)
                <tr>
                    <td colspan="5" style="background: #fff; font-size: 9.5px; color: #555; padding-left: 20px;">
                        <em>Obs: {{ $test->observations }}</em>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="5" class="text-center" style="color: #777;">Nenhum teste vinculado a esta solicitação.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($order->notes)
<div class="section-box">
    <div class="section-title">4. Observações e Interpretação Clínica</div>
    <div class="section-body">
        {!! nl2br(e($order->notes)) !!}
    </div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $order->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">Veterinário Solicitante</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">Responsável Técnico Laboratorial</div>
                <div class="signature-sub">{{ branding_clinic_name() }}</div>
            </td>
        </tr>
    </table>
</div>
@endsection
