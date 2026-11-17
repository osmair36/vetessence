@extends('layouts.adminlte', ['title' => 'Novo Transporte — Táxi Pet'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-shuttle-van mr-2"></i>Novo Transporte</h3>
    </div>
    <div class="card-body">
        @include('pet-transports._form', ['transport' => null, 'submitLabel' => 'Agendar Transporte'])
    </div>
</div>
@endsection
