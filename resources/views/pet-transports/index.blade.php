@extends('layouts.adminlte', ['title' => 'Táxi Pet'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shuttle-van mr-2"></i>Táxi Pet</h3>
        <div class="card-tools">
            <a href="{{ route('pet-transports.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Transporte
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="close" data-dismiss="alert">&times;</button></div>
        @endif

        {{-- Filtros --}}
        <form method="GET" class="form-inline mb-3">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">Todos os status</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm mr-2">
            <button class="btn btn-sm btn-secondary">Filtrar</button>
            @if(request()->hasAny(['status','date']))
                <a href="{{ route('pet-transports.index') }}" class="btn btn-sm btn-outline-secondary ml-2">Limpar</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Pet</th>
                        <th>Tutor</th>
                        <th>Coleta</th>
                        <th>Origem → Destino</th>
                        <th>Motorista</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th style="width:100px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transports as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->pet->name ?? '—' }}</td>
                        <td>{{ $t->tutor->name ?? '—' }}</td>
                        <td>{{ $t->pickup_at->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ Str::limit($t->origin_address, 20) }} → {{ Str::limit($t->destination_address, 20) }}</td>
                        <td>{{ $t->driver->name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $t->status_color }}">{{ $t->status_label }}</span>
                        </td>
                        <td>R$ {{ number_format($t->price, 2, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('pet-transports.show', $t) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('pet-transports.edit', $t) }}" class="btn btn-xs btn-warning"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">Nenhum transporte encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $transports->withQueryString()->links() }}
    </div>
</div>
@endsection
