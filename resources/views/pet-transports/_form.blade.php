{{-- Formulário compartilhado Create / Edit --}}
@if($transport)
    <form method="POST" action="{{ route('pet-transports.update', $transport) }}">
        @method('PUT')
@else
    <form method="POST" action="{{ route('pet-transports.store') }}">
@endif
    @csrf

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Pet <span class="text-danger">*</span></label>
                <select name="pet_id" class="form-control @error('pet_id') is-invalid @enderror" required>
                    <option value="">Selecione…</option>
                    @foreach($pets as $pet)
                        <option value="{{ $pet->id }}" {{ old('pet_id', $transport?->pet_id) == $pet->id ? 'selected' : '' }}>
                            {{ $pet->name }} ({{ $pet->tutors->first()?->name ?? '—' }})
                        </option>
                    @endforeach
                </select>
                @error('pet_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Tutor</label>
                <select name="tutor_id" class="form-control @error('tutor_id') is-invalid @enderror">
                    <option value="">Selecione…</option>
                    @foreach($tutors as $tutor)
                        <option value="{{ $tutor->id }}" {{ old('tutor_id', $transport?->tutor_id) == $tutor->id ? 'selected' : '' }}>
                            {{ $tutor->name }}
                        </option>
                    @endforeach
                </select>
                @error('tutor_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Data/Hora de Coleta <span class="text-danger">*</span></label>
                <input type="datetime-local" name="pickup_at" class="form-control @error('pickup_at') is-invalid @enderror"
                    value="{{ old('pickup_at', $transport?->pickup_at?->format('Y-m-d\TH:i')) }}" required>
                @error('pickup_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Data/Hora de Entrega</label>
                <input type="datetime-local" name="delivery_at" class="form-control @error('delivery_at') is-invalid @enderror"
                    value="{{ old('delivery_at', $transport?->delivery_at?->format('Y-m-d\TH:i')) }}">
                @error('delivery_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Endereço de Origem <span class="text-danger">*</span></label>
                <input type="text" name="origin_address" class="form-control @error('origin_address') is-invalid @enderror"
                    value="{{ old('origin_address', $transport?->origin_address) }}" required>
                @error('origin_address')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Endereço de Destino <span class="text-danger">*</span></label>
                <input type="text" name="destination_address" class="form-control @error('destination_address') is-invalid @enderror"
                    value="{{ old('destination_address', $transport?->destination_address) }}" required>
                @error('destination_address')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Motorista</label>
                <select name="driver_id" class="form-control @error('driver_id') is-invalid @enderror">
                    <option value="">Selecione…</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ old('driver_id', $transport?->driver_id) == $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }}
                        </option>
                    @endforeach
                </select>
                @error('driver_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Unidade</label>
                <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                    <option value="">Selecione…</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $transport?->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Valor (R$)</label>
                <input type="number" name="price" step="0.01" min="0"
                    class="form-control @error('price') is-invalid @enderror"
                    value="{{ old('price', $transport?->price ?? 0) }}">
                @error('price')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    @if($transport)
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control @error('status') is-invalid @enderror">
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" {{ old('status', $transport->status) == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
    @endif

    <div class="form-group">
        <label>Observações</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $transport?->notes) }}</textarea>
        @error('notes')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="d-flex">
        <button type="submit" class="btn btn-primary mr-2">{{ $submitLabel }}</button>
        <a href="{{ route('pet-transports.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
