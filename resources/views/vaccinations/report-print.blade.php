@extends('layouts.print', [
    'title' => 'Relatório Geral de Vacinações',
    'subtitle' => $periodSubtitle ?? 'Listagem Geral de Imunizações',
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Relatório de Vacinações',
    'pageOrientation' => 'landscape',
])

@section('content')
<div class="section-box">
    <div class="section-title">Resumo do Período</div>
    <div class="section-body">
        <p style="margin: 0;"><strong>Total de Imunizações Listadas:</strong> {{ $vaccinations->count() }} registro(s)</p>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Relação de Vacinações</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 10%;">Data</th>
                    <th style="width: 20%;">Paciente</th>
                    <th style="width: 20%;">Tutor / Contato</th>
                    <th style="width: 20%;">Vacina / Laboratório</th>
                    <th style="width: 10%;">Lote</th>
                    <th style="width: 10%;">Próx. Dose</th>
                    <th style="width: 10%;">Veterinário</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vaccinations as $vax)
                <tr>
                    <td>{{ $vax->date ? $vax->date->format('d/m/Y') : ($vax->applied_at ? \Carbon\Carbon::parse($vax->applied_at)->format('d/m/Y') : '-') }}</td>
                    <td><strong>{{ $vax->pet->name ?? '-' }}</strong> <small>({{ $vax->pet->breed ?? 'SRD' }})</small></td>
                    <td>{{ $vax->pet->tutors->pluck('name')->join(', ') ?: '-' }}</td>
                    <td><strong>{{ $vax->vaccine ?? $vax->name ?? '-' }}</strong> @if($vax->laboratory ?? $vax->manufacturer)<br><small>{{ $vax->laboratory ?? $vax->manufacturer }}</small>@endif</td>
                    <td>{{ $vax->batch ?? $vax->lot ?? '-' }}</td>
                    <td>
                        @php
                            $next = $vax->next_due_date ?? $vax->next_date ?? $vax->revaccination_at;
                        @endphp
                        {{ $next ? \Carbon\Carbon::parse($next)->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $vax->vet->name ?? $vax->veterinarian->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center" style="color: #777;">Nenhuma vacinação encontrada para os filtros selecionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
