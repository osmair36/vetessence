@extends('layouts.adminlte', ['title' => 'Editar Transporte — Táxi Pet'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shuttle-van mr-2"></i>Editar Transporte #{{ $petTransport->id }}</h3>
        <div class="card-tools">
            <a href="{{ route('pet-transports.show', $petTransport) }}" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i> Ver
            </a>
        </div>
    </div>
    <div class="card-body">
        @include('pet-transports._form', [
            'transport'   => $petTransport,
            'submitLabel' => 'Salvar Alterações',
        ])
    </div>
</div>
@endsection
