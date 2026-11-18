@extends('layouts.adminlte', ['title' => 'Transporte #' . $petTransport->id . ' — Táxi Pet'])

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shuttle-van mr-2"></i>Transporte #{{ $petTransport->id }}</h3>
                <div class="card-tools">
                    <a href="{{ route('pet-transports.print', $petTransport) }}" target="_blank" class="btn btn-sm btn-secondary">
                        <i class="fas fa-print"></i> Imprimir OS
                    </a>
                    <a href="{{ route('pet-transports.pdf', $petTransport) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-file-pdf"></i> Baixar PDF
                    </a>
                    <a href="{{ route('pet-transports.edit', $petTransport) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    @if(!in_array($petTransport->status, ['collected','in_route']))
                    <form method="POST" action="{{ route('pet-transports.destroy', $petTransport) }}" class="d-inline"
                          onsubmit="return confirm('Excluir este transporte?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Excluir</button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <dl class="row">
                    <dt class="col-sm-4">Pet</dt>
                    <dd class="col-sm-8">{{ $petTransport->pet->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Tutor</dt>
                    <dd class="col-sm-8">{{ $petTransport->tutor->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Motorista</dt>
                    <dd class="col-sm-8">{{ $petTransport->driver->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Unidade</dt>
                    <dd class="col-sm-8">{{ $petTransport->branch->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Coleta</dt>
                    <dd class="col-sm-8">{{ $petTransport->pickup_at->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-4">Entrega</dt>
                    <dd class="col-sm-8">{{ $petTransport->delivery_at ? $petTransport->delivery_at->format('d/m/Y H:i') : '—' }}</dd>

                    <dt class="col-sm-4">Origem</dt>
                    <dd class="col-sm-8">{{ $petTransport->origin_address }}</dd>

                    <dt class="col-sm-4">Destino</dt>
                    <dd class="col-sm-8">{{ $petTransport->destination_address }}</dd>

                    <dt class="col-sm-4">Valor</dt>
                    <dd class="col-sm-8">R$ {{ number_format($petTransport->price, 2, ',', '.') }}</dd>

                    <dt class="col-sm-4">Observações</dt>
                    <dd class="col-sm-8">{{ $petTransport->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Status</h3>
            </div>
            <div class="card-body text-center">
                <span class="badge badge-{{ $petTransport->status_color }} badge-lg" style="font-size:1rem;padding:.5rem 1rem">
                    {{ $petTransport->status_label }}
                </span>
                <hr>
                <p class="text-muted small mb-2">Atualizar status:</p>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    @foreach(\App\Models\PetTransport::STATUSES as $key => $label)
                        @if($key !== $petTransport->status)
                        <button class="btn btn-sm btn-outline-secondary status-btn mb-1" data-status="{{ $key }}">
                            {{ $label }}
                        </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @if($petTransport->invoice)
        <div class="card">
            <div class="card-header"><h3 class="card-title">Fatura</h3></div>
            <div class="card-body">
                <a href="{{ route('invoices.show', $petTransport->invoice) }}" class="btn btn-block btn-outline-primary btn-sm">
                    <i class="fas fa-file-invoice-dollar"></i> Ver Fatura #{{ $petTransport->invoice->id }}
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const status = this.dataset.status;
        fetch('{{ route("pet-transports.update-status", $petTransport) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ status }),
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    });
});
</script>
@endpush
