@extends('layouts.adminlte', ['title' => 'Registrar Vacina'])

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <form action="{{ route('vaccinations.store') }}" method="POST">
            @csrf
            @if(!empty($completingVaccination))
                <input type="hidden" name="complete_vaccination_id" value="{{ $completingVaccination->id }}">
                <div class="alert alert-info">
                    <i class="fas fa-check-circle mr-1"></i>
                    Registrando aplicação para concluir a pendência de <strong>{{ $completingVaccination->vaccine }}</strong>
                    (prevista para {{ $completingVaccination->next_date ? $completingVaccination->next_date->format('d/m/Y') : '-' }}).
                    Os lembretes futuros dessa pendência serão cancelados automaticamente ao salvar.
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Pet *</label>
                        <x-tom-select name="pet_id" :value="old('pet_id', $selectedPet->id ?? '')" required>
                            @foreach($pets as $pet)
                            <option value="{{ $pet->id }}" {{ old('pet_id', $selectedPet->id ?? '') == $pet->id ? 'selected' : '' }}>{{ $pet->name }} - {{ $pet->tutors->first()->name ?? 'Sem tutor' }}</option>
                            @endforeach
                        </x-tom-select>
                        @error('pet_id')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Vacina *</label>
                                <input type="text" name="vaccine" value="{{ old('vaccine', $completingVaccination->vaccine ?? '') }}" required class="form-control @error('vaccine') is-invalid @enderror" placeholder="Ex: V10 Filhote, Antirrábica, Giardia">
                                @error('vaccine')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dose</label>
                                <input type="text" name="dose" value="{{ old('dose') }}" class="form-control @error('dose') is-invalid @enderror" placeholder="Ex: 1ª dose, Reforço">
                                @error('dose')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Produto (para protocolo e baixa de estoque)</label>
                        <select name="product_id" id="vac_product_id" class="form-control">
                            <option value="">— Sem produto —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-price="{{ $p->sale_price }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} (estoque: {{ $p->stock }}) — R$ {{ number_format($p->sale_price, 2, ',', '.') }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data de Aplicação *</label>
                                <input type="date" name="date" id="vac_date" value="{{ old('date', date('Y-m-d')) }}" required class="form-control @error('date') is-invalid @enderror">
                                @error('date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Próxima Dose</label>
                                <input type="date" name="next_date" id="vac_next_date" value="{{ old('next_date') }}" class="form-control @error('next_date') is-invalid @enderror">
                                <input type="hidden" name="next_date_manual" id="vac_next_date_manual" value="{{ old('next_date') ? '1' : '0' }}">
                                <small id="vac_schedule_feedback" class="form-text mt-1"></small>
                                @error('next_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Laboratório</label>
                                <input type="text" name="laboratory" list="laboratories-list" value="{{ old('laboratory', old('manufacturer', $completingVaccination->laboratory ?? '')) }}" class="form-control @error('laboratory') is-invalid @enderror" placeholder="Ex: ZOETIS, VIRBAC, PFIZER, MSD">
                                <datalist id="laboratories-list">
                                    @foreach(($laboratories ?? []) as $lab)
                                    <option value="{{ $lab }}">
                                    @endforeach
                                </datalist>
                                @error('laboratory')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Lote / Partida</label>
                                <input type="text" name="batch" value="{{ old('batch') }}" class="form-control @error('batch') is-invalid @enderror" placeholder="Ex: 004/26">
                                @error('batch')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Veterinário *</label>
                        <select name="vet_id" required class="form-control @error('vet_id') is-invalid @enderror">
                            <option value="">Selecione...</option>
                            @foreach($veterinarians as $vet)
                            <option value="{{ $vet->id }}" {{ old('vet_id') == $vet->id ? 'selected' : '' }}>{{ $vet->name }}</option>
                            @endforeach
                        </select>
                        @error('vet_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="notes" rows="2" class="wysiwyg form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('vaccinations.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('partials.application-schedule-script', [
    'scheduleConfig' => [
        'context' => 'vaccination',
        'petSelector' => '[name="pet_id"]',
        'productSelector' => '#vac_product_id',
        'itemNameSelector' => '[name="vaccine"]',
        'doseSelector' => '[name="dose"]',
        'applicationDateSelector' => '#vac_date',
        'nextDateSelector' => '#vac_next_date',
        'manualFlagSelector' => '#vac_next_date_manual',
        'feedbackSelector' => '#vac_schedule_feedback',
        'initiallyManual' => (bool) old('next_date'),
    ],
])
@endsection
