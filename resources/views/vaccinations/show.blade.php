@extends('layouts.adminlte', ['title' => 'Vacina - ' . ($vaccination->pet->name ?? '')])

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Pet</small>
                        <p class="font-weight-bold">
                            @if($vaccination->pet)
                                <a href="{{ route('pets.show', $vaccination->pet) }}">{{ $vaccination->pet->name }}</a>
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Vacina</small>
                        <p class="font-weight-bold">{{ $vaccination->vaccine }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Data de Aplicação</small>
                        <p>{{ $vaccination->date ? $vaccination->date->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Próxima Dose</small>
                        <p>{{ $vaccination->next_date ? $vaccination->next_date->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Laboratório</small>
                        <p>{{ $vaccination->laboratory ?? '-' }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Lote / Partida</small>
                        <p>{{ $vaccination->batch ?? $vaccination->lot ?? '-' }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Dose</small>
                        <p>{{ $vaccination->dose ?? '-' }}</p>
                    </div>
                    <div class="col-6">
                        <small class="text-muted text-uppercase">Veterinário</small>
                        <p>{{ $vaccination->vet->name ?? '-' }}</p>
                    </div>
                </div>
                @if($vaccination->notes)
                <hr>
                <small class="text-muted text-uppercase">Observações</small>
                <p class="mt-1">{!! $vaccination->notes !!}</p>
                @endif
            </div>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <a href="{{ route('vaccinations.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Voltar</a>
            <a href="{{ route('vaccinations.edit', $vaccination) }}" class="btn btn-primary"><i class="fas fa-edit mr-1"></i>Editar</a>
        </div>
    </div>
</div>
@endsection
