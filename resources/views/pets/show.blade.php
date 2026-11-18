@extends('layouts.adminlte', ['title' => $pet->name])

@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('pets.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Voltar para lista
    </a>
    <div>
        <a href="{{ route('pets.print', $pet) }}" target="_blank" class="btn btn-outline-primary btn-sm mr-1">
            <i class="fas fa-print mr-1"></i> Imprimir Ficha
        </a>
        <a href="{{ route('pets.pdf', $pet) }}" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file-pdf mr-1"></i> Baixar PDF
        </a>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($pet->photo_url)
                <img src="{{ $pet->photo_url }}" alt="{{ $pet->name }}" class="rounded-circle mx-auto mb-3" style="width: 128px; height: 128px; object-fit: cover;">
                @else
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 96px; height: 96px; background: color-mix(in srgb, var(--brand-primary, #455e36) 15%, white);">
                    <i class="fas fa-paw" style="font-size: 2.5rem; color: var(--brand-primary, #455e36);"></i>
                </div>
                @endif
                <h5 class="font-weight-bold">{{ $pet->name }}</h5>
                <p class="text-muted mb-2">
                    @php
                        $speciesLabels = config('species');
                    @endphp
                    {{ $speciesLabels[$pet->species] ?? $pet->species }} - {{ $pet->breedRelation?->name ?? $pet->breed ?? 'SRD' }}
                </p>

                @if($pet->is_aggressive)
                <div class="alert alert-danger py-1 px-2 mb-2 font-weight-bold text-center small shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i> ANIMAL BRAVO / CUIDADO NO MANEJO
                </div>
                @endif

                <hr>

                <div class="text-left small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Gênero:</span>
                        <span>{{ $pet->gender === 'male' ? 'Macho' : 'Fêmea' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Castrado:</span>
                        <span class="badge {{ $pet->is_neutered ? 'badge-success' : 'badge-secondary' }}">{{ $pet->is_neutered ? 'Sim' : 'Não' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Idade:</span>
                        <span>{{ $pet->age ?? '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Peso:</span>
                        <span>{{ $pet->weight ? $pet->weight . ' kg' : '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Porte:</span>
                        <span>{{ ucfirst($pet->size ?? '-') }}</span>
                    </div>
                    @if($pet->color)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Pelagem/Cor:</span>
                        <span>{{ $pet->color }}</span>
                    </div>
                    @endif
                    @if($pet->microchip)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Microchip:</span>
                        <span>{{ $pet->microchip }}</span>
                    </div>
                    @endif
                    @if($pet->microchip_date)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Data Microchip:</span>
                        <span>{{ $pet->microchip_date->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($pet->rg_number)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">RG Animal:</span>
                        <span>{{ $pet->rg_number }} @if($pet->rg_issuer)({{ $pet->rg_issuer }})@endif</span>
                    </div>
                    @endif

                    @if($pet->pathologies)
                    <div class="mt-2 p-2 bg-light rounded border-left border-danger">
                        <span class="font-weight-bold text-danger d-block"><i class="fas fa-notes-medical mr-1"></i> Patologias:</span>
                        <span class="text-dark">{{ $pet->pathologies }}</span>
                    </div>
                    @endif

                    @if($pet->surgical_history)
                    <div class="mt-2 p-2 bg-light rounded border-left border-primary">
                        <span class="font-weight-bold text-primary d-block"><i class="fas fa-syringe mr-1"></i> Cirurgias Prévias:</span>
                        <span class="text-dark">{{ $pet->surgical_history }}</span>
                    </div>
                    @endif

                    @if($pet->notes)
                    <div class="mt-2 p-2 bg-light rounded border-left border-secondary">
                        <span class="font-weight-bold text-muted d-block"><i class="fas fa-comment-alt mr-1"></i> Observações:</span>
                        <span class="text-dark">{!! nl2br(e($pet->notes)) !!}</span>
                    </div>
                    @endif
                </div>

                <hr>

                <button onclick="openEditModal({{ $pet->id }})" class="btn btn-primary btn-block">
                    <i class="fas fa-edit mr-1"></i> Editar
                </button>
                <a href="{{ route('pets.timeline', $pet) }}" class="btn btn-secondary-custom btn-block mt-2">
                    <i class="fas fa-history mr-1"></i> Timeline
                </a>
                <a href="{{ route('pets.print', $pet) }}" target="_blank" class="btn btn-outline-primary btn-block mt-2">
                    <i class="fas fa-print mr-1"></i> Imprimir Ficha
                </a>
                <a href="{{ route('pets.pdf', $pet) }}" class="btn btn-outline-danger btn-block mt-2">
                    <i class="fas fa-file-pdf mr-1"></i> Baixar PDF
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Tutores</h5>
            </div>
            <div class="card-body p-0">
                @forelse($pet->tutors as $tutor)
                <a href="{{ route('tutors.show', $tutor) }}" class="text-decoration-none">
                    <div class="d-flex align-items-center p-3 border-bottom">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mr-3" style="width: 40px; height: 40px; background: color-mix(in srgb, var(--brand-primary, #455e36) 15%, white); color: var(--brand-primary, #455e36); font-weight: bold;">
                            {{ substr($tutor->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-weight-bold text-dark">{{ $tutor->name }}</div>
                            <small class="text-muted">{{ $tutor->pivot->is_primary ? 'Titular' : 'Secundário' }}</small>
                        </div>
                    </div>
                </a>
                @empty
                <p class="text-muted text-center mb-0 py-3">Nenhum tutor vinculado.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-8">
        {{-- BARRA DE AÇÕES RÁPIDAS CLÍNICAS --}}
        <div class="card mb-3 shadow-sm border-0 bg-light">
            <div class="card-body p-2 d-flex flex-wrap align-items-center">
                <span class="small font-weight-bold text-muted mr-2 mb-1"><i class="fas fa-bolt text-warning mr-1"></i> Ações Rápidas:</span>
                <a href="{{ route('medical-records.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-primary mr-1 mb-1">
                    <i class="fas fa-file-medical mr-1"></i> Atendimento
                </a>
                <a href="{{ route('vaccinations.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-success mr-1 mb-1">
                    <i class="fas fa-syringe mr-1"></i> Vacina
                </a>
                <a href="{{ route('parasite-controls.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-info mr-1 mb-1">
                    <i class="fas fa-shield-virus mr-1"></i> Vermífugo / Antiparasitário
                </a>
                <a href="{{ route('prescriptions.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-warning text-dark mr-1 mb-1">
                    <i class="fas fa-prescription mr-1"></i> Receita
                </a>
                <a href="{{ route('laboratory-orders.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-secondary mr-1 mb-1">
                    <i class="fas fa-flask mr-1"></i> Pedido Exames
                </a>
                <a href="{{ route('imaging-exams.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-dark mr-1 mb-1">
                    <i class="fas fa-x-ray mr-1"></i> Exame Imagem
                </a>
                <a href="{{ route('consent-forms.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-outline-primary mr-1 mb-1">
                    <i class="fas fa-file-contract mr-1"></i> Emitir Termo
                </a>
                <a href="{{ route('health-certificates.create') }}?pet_id={{ $pet->id }}" class="btn btn-xs btn-outline-success mr-1 mb-1">
                    <i class="fas fa-plane mr-1"></i> Atestado Viagem
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-medical mr-2"></i>Últimos Atendimentos</h5>
                <a href="{{ route('medical-records.create') }}?pet_id={{ $pet->id }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i> Novo
                </a>
            </div>
            <div class="card-body p-0">
                @if($pet->medicalRecords->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($pet->medicalRecords->take(5) as $record)
                    <a href="{{ route('medical-records.show', $record) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mr-3" style="width: 40px; height: 40px; background: color-mix(in srgb, var(--brand-primary, #455e36) 15%, white); color: var(--brand-primary, #455e36);">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div class="flex-fill">
                            <div class="font-weight-bold">{{ $record->date->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ Str::limit(strip_tags($record->diagnosis), 50) ?: 'Sem diagnóstico' }}</small>
                        </div>
                        <small class="text-muted">{{ $record->vet->name ?? '-' }}</small>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="text-muted text-center mb-0 py-4">Nenhum atendimento registrado.</p>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-syringe mr-2"></i>Vacinas</h5>
                <a href="{{ route('vaccinations.create') }}?pet_id={{ $pet->id }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i> Nova
                </a>
            </div>
            <div class="card-body p-0">
                @if($pet->vaccinations->count() > 0)
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Vacina</th>
                            <th>Data</th>
                            <th>Próxima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pet->vaccinations as $vaccination)
                        <tr>
                            <td>{{ $vaccination->vaccine }}</td>
                            <td data-order="{{ $vaccination->date->format('Y-m-d') }}">{{ $vaccination->date->format('d/m/Y') }}</td>
                            <td data-order="{{ $vaccination->next_date ? $vaccination->next_date->format('Y-m-d') : '' }}">{{ $vaccination->next_date ? $vaccination->next_date->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center mb-0 py-4">Nenhuma vacina registrada.</p>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-shield-virus mr-2"></i>Vermífugos e Antiparasitários (Simparic, etc.)</h5>
                <a href="{{ route('parasite-controls.create') }}?pet_id={{ $pet->id }}" class="btn btn-sm btn-info">
                    <i class="fas fa-plus mr-1"></i> Novo
                </a>
            </div>
            <div class="card-body p-0">
                @if($pet->parasiteControls && $pet->parasiteControls->count() > 0)
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Aplicação</th>
                            <th>Próximo Reforço</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pet->parasiteControls as $pc)
                        <tr>
                            <td><strong>{{ $pc->product_name }}</strong> @if($pc->dose)<small class="text-muted">({{ $pc->dose }})</small>@endif</td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'flea' => 'Pulgas',
                                        'tick' => 'Carrapatos',
                                        'heartworm' => 'Verme do Coração',
                                        'intestinal' => 'Vermífugo',
                                        'combination' => 'Combinado'
                                    ];
                                @endphp
                                <span class="badge badge-light border">{{ $typeLabels[$pc->type] ?? ucfirst($pc->type) }}</span>
                            </td>
                            <td>{{ $pc->application_date ? $pc->application_date->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if($pc->next_due_date)
                                    <span class="font-weight-bold {{ $pc->next_due_date->isPast() ? 'text-danger' : 'text-success' }}">
                                        {{ $pc->next_due_date->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center mb-0 py-4">Nenhum controle parasitário registrado.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade" id="petModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="petModalTitle">Editar Pet</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                @livewire('pet-form', key('pet-form-show'))
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('livewire:initialized', function() {
    Livewire.on('close-modal', function() { $('#petModal').modal('hide'); });
    Livewire.on('pet-saved', function() { location.reload(); });
});
function openEditModal(id) {
    Livewire.dispatch('editPet', { id: id });
    document.getElementById('petModalTitle').textContent = 'Editar Pet';
    $('#petModal').modal('show');
}
</script>
@endpush
