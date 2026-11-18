@extends('layouts.adminlte', ['title' => 'Tutores'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Tutores ({{ $tutors->total() }})</h3>
        <div class="card-tools">
            <button onclick="openCreateModal()" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('tutors.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-9 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nome, CPF, telefone ou e-mail..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search"></i> Filtrar</button>
                    @if(request('search'))
                        <a href="{{ route('tutors.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpar</a>
                    @endif
                </div>
            </div>
        </form>

        @if($tutors->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Pets</th>
                    <th style="width: 150px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tutors as $tutor)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle font-weight-bold" style="width: 32px; height: 32px; background: color-mix(in srgb, var(--brand-primary, #455e36) 15%, white); color: var(--brand-primary, #455e36);">
                                {{ substr($tutor->name, 0, 1) }}
                            </div>
                            <div class="ml-2">
                                <div class="font-weight-bold">{{ $tutor->name }}</div>
                                <small class="text-muted">{{ $tutor->city ?? '-' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>{{ $tutor->cpf }}</td>
                    <td>{{ $tutor->phone }}</td>
                    <td>{{ $tutor->email }}</td>
                    <td><span class="badge badge-primary">{{ $tutor->pets_count ?? $tutor->pets->count() }}</span></td>
                    <td>
                        <a href="{{ route('tutors.show', $tutor) }}" class="btn btn-action btn-info" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button onclick="openEditModal({{ $tutor->id }})" class="btn btn-action btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('tutors.destroy', $tutor) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" data-confirm="Tem certeza?" class="btn btn-action btn-danger" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-center text-muted">Nenhum registro encontrado.</p>
        @endif
    </div>
    @if($tutors->hasPages())
    <div class="card-footer clearfix">
        {{ $tutors->withQueryString()->links() }}
    </div>
    @endif
</div>

@endsection

@push('modals')
<!-- Tutor Modal -->
<div class="modal fade" id="tutorModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorModalTitle">Novo Tutor</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                @livewire('tutor-form', key('tutor-form'))
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('livewire:initialized', function() {
    Livewire.on('close-modal', function() { $('#tutorModal').modal('hide'); });
    Livewire.on('tutor-saved', function() { location.reload(); });
});
function openCreateModal() {
    Livewire.dispatch('resetForm');
    document.getElementById('tutorModalTitle').textContent = 'Novo Tutor';
    $('#tutorModal').modal('show');
}
function openEditModal(id) {
    Livewire.dispatch('editTutor', { id: id });
    document.getElementById('tutorModalTitle').textContent = 'Editar Tutor';
    $('#tutorModal').modal('show');
}
</script>
@endpush
