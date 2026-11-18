@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Ficha Odontológica — #{{ $chart->id }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Animal:</span><br>
                    <span class="info-value">{{ $chart->pet->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Espécie / Raça:</span><br>
                    <span class="info-value">{{ $chart->pet->species ?? '-' }} / {{ $chart->pet->breed ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Data do Exame:</span><br>
                    <span class="info-value">{{ $chart->examination_date ? \Carbon\Carbon::parse($chart->examination_date)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Médico Veterinário:</span><br>
                    <span class="info-value">{{ $chart->vet->name ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Avaliação Clínica</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Tipo de Procedimento:</span><br>
                    <span class="info-value">{{ $chart->procedure_type ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Índice de Tártaro:</span><br>
                    <span class="info-value">{{ $chart->tartar_index ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Índice de Gengivite:</span><br>
                    <span class="info-value">{{ $chart->gingivitis_index ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Halitose:</span><br>
                    <span class="info-value">{{ $chart->halitosis ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:67%">
                    <span class="info-label">Observações Gerais:</span><br>
                    <span class="info-value">{{ $chart->general_notes ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($chart->conditions && $chart->conditions->count())
<div class="section-box">
    <div class="section-title">Condições Dentárias Registradas ({{ $chart->conditions->count() }} dente(s))</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Dente Nº</th>
                    <th>Quadrante</th>
                    <th>Condição</th>
                    <th>Severidade</th>
                    <th>Tratamento</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chart->conditions as $cond)
                <tr>
                    <td class="text-center">{{ $cond->tooth_number ?? '-' }}</td>
                    <td class="text-center">{{ $cond->quadrant ?? '-' }}</td>
                    <td>{{ $cond->condition_type ?? '-' }}</td>
                    <td>
                        @if($cond->severity === 'mild') <span class="badge badge-warning">Leve</span>
                        @elseif($cond->severity === 'moderate') <span class="badge badge-warning">Moderada</span>
                        @elseif($cond->severity === 'severe') <span class="badge badge-danger">Grave</span>
                        @else {{ $cond->severity ?? '-' }} @endif
                    </td>
                    <td>{{ $cond->treatment ?? '-' }}</td>
                    <td>{{ $cond->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="section-box">
    <div class="section-title">Condições Dentárias</div>
    <div class="section-body"><p style="color:#888; text-align:center;">Nenhuma condição dentária registrada.</p></div>
</div>
@endif

@if($chart->recommendations)
<div class="section-box">
    <div class="section-title">Recomendações</div>
    <div class="section-body"><p>{{ $chart->recommendations }}</p></div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $chart->vet->name ?? 'Médico Veterinário' }}</div>
                <div class="signature-sub">CRMV: {{ $chart->vet->crmv ?? '—' }}</div>
            </td>
        </tr>
    </table>
</div>
@endsection
