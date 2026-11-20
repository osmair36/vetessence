@extends('layouts.adminlte', ['title' => 'Vacinas'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Vacinas ({{ $vaccinations->total() }})</h3>
        <div class="card-tools">
            <a href="{{ route('vaccinations.report.print') }}" target="_blank" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-print"></i> Imprimir Relatório
            </a>
            <a href="{{ route('vaccinations.report.pdf') }}" class="btn btn-outline-danger btn-sm mr-1">
                <i class="fas fa-file-pdf"></i> Baixar PDF
            </a>
            <a href="{{ route('vaccinations.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('vaccinations.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por vacina, laboratório, lote ou nome do pet..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Data Inicial">
                </div>
                <div class="col-md-4 mb-2 d-flex">
                    <input type="date" name="date_to" class="form-control mr-1" value="{{ request('date_to') }}" placeholder="Data Final">
                    <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search"></i> Filtrar</button>
                    @if(request('search') || request('date_from') || request('date_to') || request('pet_id'))
                        <a href="{{ route('vaccinations.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpar</a>
                    @endif
                </div>
            </div>
        </form>

        @if($vaccinations->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Vacina</th>
                    <th>Laboratório</th>
                    <th>Lote</th>
                    <th>Data Aplicação</th>
                    <th>Próxima Dose</th>
                    <th>Veterinário</th>
                    <th style="width: 140px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vaccinations as $vac)
                <tr>
                    <td>
                        @if($vac->pet)
                            <a href="{{ route('pets.show', $vac->pet) }}"><strong>{{ $vac->pet->name }}</strong></a>
                        @else
                            <strong>-</strong>
                        @endif
                    </td>
                    <td>
                        {{ $vac->vaccine }}
                        @if($vac->status === 'scheduled')
                            <span class="badge badge-info ml-1">Agendada</span>
                        @endif
                        @if($vac->dose)
                            <br><small class="text-muted">{{ $vac->dose }}</small>
                        @endif
                    </td>
                    <td>{{ $vac->laboratory ?? '-' }}</td>
                    <td>{{ $vac->batch ?? $vac->lot ?? '-' }}</td>
                    <td data-order="{{ $vac->date ? $vac->date->format('Y-m-d') : '' }}">{{ $vac->date ? $vac->date->format('d/m/Y') : '-' }}</td>
                    <td data-order="{{ $vac->next_date ? $vac->next_date->format('Y-m-d') : '' }}">{{ $vac->next_date ? $vac->next_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $vac->vet->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('vaccinations.show', $vac) }}" class="btn btn-action btn-info" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($vac->pet)
                        <a href="{{ route('pets.vaccinations.card.print', $vac->pet) }}" target="_blank" class="btn btn-action btn-secondary" title="Carteira de Vacinação">
                            <i class="fas fa-id-card"></i>
                        </a>
                        <a href="{{ route('vaccinations.certificate', $vac->pet) }}" class="btn btn-action btn-warning" title="Certificado PDF">
                            <i class="fas fa-certificate"></i>
                        </a>
                        @endif
                        <a href="{{ route('vaccinations.edit', $vac) }}" class="btn btn-action btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-center text-muted">Nenhum registro encontrado.</p>
        @endif
    </div>
    @if($vaccinations->hasPages())
    <div class="card-footer clearfix">
        {{ $vaccinations->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
