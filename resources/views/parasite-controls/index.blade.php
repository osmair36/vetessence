@extends('layouts.adminlte', ['title' => 'Controle Parasitário'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Controle Parasitário</h3>
        <div class="card-tools">
            <a href="{{ route('parasite-controls.report.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-print"></i> Imprimir Relatório
            </a>
            <a href="{{ route('parasite-controls.report.pdf', request()->all()) }}" class="btn btn-outline-danger btn-sm mr-1">
                <i class="fas fa-file-pdf"></i> Baixar PDF
            </a>
            <a href="{{ route('parasite-controls.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Novo</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row mb-3">
            <div class="col-md-4">
                <select name="pet_id" class="form-control">
                    <option value="">Todos os pets</option>
                    @foreach($pets as $pet)
                        <option value="{{ $pet->id }}" {{ request('pet_id') == $pet->id ? 'selected' : '' }}>{{ $pet->name }} - {{ $pet->tutors->first()->name ?? 'Sem tutor' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-control">
                    <option value="">Todos os tipos</option>
                    <option value="flea" {{ request('type') == 'flea' ? 'selected' : '' }}>Pulga</option>
                    <option value="tick" {{ request('type') == 'tick' ? 'selected' : '' }}>Carrapato</option>
                    <option value="heartworm" {{ request('type') == 'heartworm' ? 'selected' : '' }}>Verme do Coração</option>
                    <option value="intestinal" {{ request('type') == 'intestinal' ? 'selected' : '' }}>Vermífugo</option>
                    <option value="combination" {{ request('type') == 'combination' ? 'selected' : '' }}>Combinado</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </form>

        @if($controls->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Data Aplicação</th>
                    <th>Próxima</th>
                    <th>Veterinário</th>
                    <th style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($controls as $c)
                <tr>
                    <td><strong>{{ $c->pet->name ?? '-' }}</strong></td>
                    <td>{{ $c->product_name }}</td>
                    <td>
                        @php
                            $typeLabels = ['flea' => 'Pulga', 'tick' => 'Carrapato', 'heartworm' => 'Verme do Coração', 'intestinal' => 'Vermífugo', 'combination' => 'Combinado'];
                        @endphp
                        {{ $typeLabels[$c->type] ?? $c->type }}
                    </td>
                    <td data-order="{{ $c->application_date->format('Y-m-d') }}">{{ $c->application_date->format('d/m/Y') }}</td>
                    <td data-order="{{ $c->next_due_date ? $c->next_due_date->format('Y-m-d') : '' }}">{{ $c->next_due_date ? $c->next_due_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $c->vet->name ?? '-' }}</td>
                    <td>
                        <a href="{{ route('parasite-controls.show', $c) }}" class="btn btn-action btn-info"><i class="fas fa-eye"></i></a>
                        @if($c->pet)
                        <a href="{{ route('pets.parasite-controls.print', $c->pet) }}" target="_blank" class="btn btn-action btn-secondary" title="Histórico do Pet"><i class="fas fa-print"></i></a>
                        @endif
                        <a href="{{ route('parasite-controls.edit', $c) }}" class="btn btn-action btn-primary"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @else
        <p class="text-center text-muted">Nenhum registro encontrado.</p>
        @endif
    </div>
</div>
@endsection
