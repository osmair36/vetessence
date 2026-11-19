@extends('layouts.adminlte', ['title' => 'Editar Vacina'])

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <form action="{{ route('vaccinations.update', $vaccination) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Pet</label>
                        <input type="text" value="{{ $vaccination->pet->name ?? '-' }}" class="form-control" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Vacina *</label>
                                <input type="text" name="vaccine" value="{{ old('vaccine', $vaccination->vaccine) }}" required class="form-control @error('vaccine') is-invalid @enderror">
                                @error('vaccine')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Dose</label>
                                <input type="text" name="dose" value="{{ old('dose', $vaccination->dose) }}" class="form-control @error('dose') is-invalid @enderror" placeholder="Ex: 1ª dose, Reforço">
                                @error('dose')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Produto (para baixa de estoque)</label>
                        <select name="product_id" class="form-control">
                            <option value="">— Sem produto —</option>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id', $vaccination->product_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} (estoque: {{ $p->stock }}) — R$ {{ number_format($p->sale_price, 2, ',', '.') }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data de Aplicação *</label>
                                <input type="date" name="date" value="{{ old('date', $vaccination->date ? $vaccination->date->format('Y-m-d') : '') }}" required class="form-control @error('date') is-invalid @enderror">
                                @error('date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Próxima Dose</label>
                                <input type="date" name="next_date" value="{{ old('next_date', $vaccination->next_date ? $vaccination->next_date->format('Y-m-d') : '') }}" class="form-control @error('next_date') is-invalid @enderror">
                                @error('next_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Laboratório</label>
                                <input type="text" name="laboratory" list="laboratories-list" value="{{ old('laboratory', $vaccination->laboratory ?? $vaccination->manufacturer) }}" class="form-control @error('laboratory') is-invalid @enderror" placeholder="Ex: ZOETIS, VIRBAC, PFIZER, MSD">
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
                                <input type="text" name="batch" value="{{ old('batch', $vaccination->batch ?? $vaccination->lot) }}" class="form-control @error('batch') is-invalid @enderror">
                                @error('batch')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Veterinário *</label>
                        <select name="vet_id" required class="form-control @error('vet_id') is-invalid @enderror">
                            @foreach($veterinarians as $vet)
                            <option value="{{ $vet->id }}" {{ old('vet_id', $vaccination->vet_id) == $vet->id ? 'selected' : '' }}>{{ $vet->name }}</option>
                            @endforeach
                        </select>
                        @error('vet_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="notes" rows="2" class="wysiwyg form-control @error('notes') is-invalid @enderror">{!! old('notes', $vaccination->notes) !!}</textarea>
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
@endsection
