@extends('layouts.print', [
    'title' => 'Relatório Geral de Controle de Parasitas',
    'subtitle' => $periodSubtitle ?? 'Listagem Geral de Aplicações',
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Relatório - Controle Parasitário',
    'pageOrientation' => 'landscape',
])

@section('content')
<div class="section-box">
    <div class="section-title">Resumo</div>
    <div class="section-body">
        <p style="margin: 0;"><strong>Total de Registros:</strong> {{ $controls->count() }} aplicação(ões)</p>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Relação de Aplicações de Antiparasitários e Vermífugos</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 10%;">Data</th>
                    <th style="width: 20%;">Paciente</th>
                    <th style="width: 20%;">Tutor / Contato</th>
                    <th style="width: 20%;">Produto / Princípio Ativo</th>
                    <th style="width: 10%;">Tipo</th>
                    <th style="width: 10%;">Próx. Dose</th>
                    <th style="width: 10%;">Veterinário</th>
                </tr>
            </thead>
            <tbody>
                @forelse($controls as $ctrl)
                <tr>
                    <td>{{ $ctrl->application_date ? $ctrl->application_date->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ $ctrl->pet->name ?? '-' }}</strong> <small>({{ $ctrl->pet->breed ?? 'SRD' }})</small></td>
                    <td>{{ $ctrl->pet->tutors->pluck('name')->join(', ') ?: '-' }}</td>
                    <td><strong>{{ $ctrl->product_name }}</strong> @if($ctrl->active_ingredient)<br><small>{{ $ctrl->active_ingredient }}</small>@endif</td>
                    <td>{{ ucfirst($ctrl->type ?? '-') }}</td>
                    <td><strong>{{ $ctrl->next_due_date ? $ctrl->next_due_date->format('d/m/Y') : '-' }}</strong></td>
                    <td>{{ $ctrl->vet->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center" style="color: #777;">Nenhum registro encontrado para os filtros selecionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
