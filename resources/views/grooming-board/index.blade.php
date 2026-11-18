@extends('layouts.adminlte', ['title' => 'Agenda de Banho & Tosa'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cut mr-2"></i>Agenda de Banho &amp; Tosa</h3>
        <div class="card-tools d-flex align-items-center gap-2">
            <form method="GET" class="d-inline-flex align-items-center mr-2">
                <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm mr-2">
                <button class="btn btn-sm btn-secondary mr-2">Filtrar</button>
            </form>
            <a href="{{ route('grooming-board.print', ['date' => $date]) }}" target="_blank" class="btn btn-secondary btn-sm mr-1">
                <i class="fas fa-print"></i> Imprimir Dia
            </a>
            <a href="{{ route('grooming-board.pdf', ['date' => $date]) }}" class="btn btn-success btn-sm mr-2">
                <i class="fas fa-file-pdf"></i> PDF Dia
            </a>
            <a href="{{ route('appointments.create') }}?type=grooming" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Agendamento
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
        @endif

        <div class="row" id="grooming-board">
            @foreach($statuses as $key => $meta)
            <div class="col-md-2 col-sm-4 mb-3">
                <div class="card card-outline card-{{ $meta['color'] }}">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">
                            <span class="badge badge-{{ $meta['color'] }}">{{ $appointments->get($key, collect())->count() }}</span>
                            {{ $meta['label'] }}
                        </h6>
                    </div>
                    <div class="card-body p-2" style="min-height:200px">
                        @foreach($appointments->get($key, collect()) as $appt)
                        <div class="card card-sm mb-2 shadow-sm border-left-{{ $meta['color'] }}">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong class="d-block">{{ $appt->pet->name ?? '—' }}</strong>
                                    <div class="btn-group btn-group-xs">
                                        <a href="{{ route('grooming.order.print', $appt) }}" target="_blank" class="btn btn-xs btn-default text-dark" title="Imprimir OS Banho & Tosa">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="{{ route('grooming.order.pdf', $appt) }}" class="btn btn-xs btn-default text-danger" title="Baixar PDF OS">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    {{ $appt->pet->tutors->first()->name ?? '' }}<br>
                                    {{ \Carbon\Carbon::parse($appt->time)->format('H:i') }}
                                    @if($appt->groomer) · {{ $appt->groomer->name }} @endif
                                </small>
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @foreach(array_keys($statuses) as $s)
                                        @if($s !== $key)
                                        <button class="btn btn-xs btn-outline-secondary status-btn"
                                            data-id="{{ $appt->id }}" data-status="{{ $s }}"
                                            title="{{ $statuses[$s]['label'] }}">
                                            {{ $statuses[$s]['label'] }}
                                        </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const status = this.dataset.status;
        fetch(`/grooming-board/${id}/status`, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({status})
        }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
    });
});
</script>
@endpush
