@extends('layouts.adminlte', ['title' => 'Central de Lembretes e Notificações por WhatsApp'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h4 class="mb-0"><i class="fab fa-whatsapp text-success mr-2"></i>Central de Lembretes e Notificações por WhatsApp</h4>
        <small class="text-muted">
            Provedor WhatsApp: <strong>{{ $stats['whatsapp_provider'] ? strtoupper($stats['whatsapp_provider']) : 'Não configurado' }}</strong>
            | Envio Automático:
            @if($stats['auto_send_enabled'])
                <span class="badge badge-success">ATIVADO ({{ $settings['daily_run_time'] }} America/Sao_Paulo)</span>
            @else
                <span class="badge badge-secondary">DESATIVADO</span>
            @endif
            | Serviços:
            <span class="badge badge-{{ $settings['enable_vaccinations'] ? 'success' : 'light border text-muted' }}">Vacinas</span>
            <span class="badge badge-{{ $settings['enable_birthdays'] ? 'success' : 'light border text-muted' }}">Aniversariantes</span>
            <span class="badge badge-{{ $settings['enable_grooming'] ? 'success' : 'light border text-muted' }}">Banho e Tosa</span>
            @if($stats['last_run_at'])
                | Última rotina: <strong>{{ \Carbon\Carbon::parse($stats['last_run_at'])->format('d/m/Y H:i') }}</strong>
            @endif
        </small>
    </div>
    <div class="mt-2 mt-md-0">
        @if($canManageSettings)
            <a href="{{ route('configuracoes.notificacoes.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
                <i class="fas fa-plug mr-1"></i> API WhatsApp
            </a>
        @endif
        <a href="{{ route('vaccinations.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Nova Vacinação
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show">
        <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

{{-- Indicadores Clicaveis no Topo --}}
<div class="row mb-3">
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'due_today']) }}" class="text-decoration-none">
            <div class="info-box bg-warning mb-0 {{ $tab === 'acompanhamento' && $bucket === 'due_today' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vacinas Hoje</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['due_today'] }}</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'overdue']) }}" class="text-decoration-none">
            <div class="info-box bg-danger mb-0 {{ $tab === 'acompanhamento' && $bucket === 'overdue' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vacinas em Atraso</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['overdue'] }}</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'today']) }}" class="text-decoration-none">
            <div class="info-box mb-0 {{ $tab === 'aniversariantes' ? 'elevation-3' : '' }}" style="background-color: #e83e8c; color: #fff;">
                <span class="info-box-icon"><i class="fas fa-birthday-cake"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Aniversariantes Hoje</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['birthdays_today'] ?? 0 }} <small>(Mês: {{ $stats['birthdays_month'] ?? 0 }})</small></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'today']) }}" class="text-decoration-none">
            <div class="info-box bg-teal mb-0 {{ $tab === 'banho_tosa' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-bath"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Banho e Tosa Hoje</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['grooming_today'] ?? 0 }} <small>(7d: {{ $stats['grooming_next_7_days'] ?? 0 }})</small></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-12 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'sent']) }}" class="text-decoration-none">
            <div class="info-box mb-0 border">
                <span class="info-box-icon bg-success"><i class="fab fa-whatsapp"></i></span>
                <div class="info-box-content text-dark">
                    <span class="info-box-text">WhatsApp Enviadas</span>
                    <span class="info-box-number">{{ $stats['messages_sent'] }} <small class="text-muted">(Entregues: {{ $stats['messages_delivered'] }} | Lidas: {{ $stats['messages_read'] }})</small></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-12 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'pending']) }}" class="text-decoration-none">
            <div class="info-box mb-0 border">
                <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
                <div class="info-box-content text-dark">
                    <span class="info-box-text">Aguardando Envio</span>
                    <span class="info-box-number">{{ $stats['messages_pending'] }}</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-12 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'failed']) }}" class="text-decoration-none">
            <div class="info-box mb-0 border">
                <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                <div class="info-box-content text-dark">
                    <span class="info-box-text">Com Falha de Envio</span>
                    <span class="info-box-number">{{ $stats['messages_failed'] }}</span>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Navegacao Principal em Abas --}}
<div class="card card-primary card-outline card-outline-tabs">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'acompanhamento' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => $bucket]) }}">
                    <i class="fas fa-syringe mr-1"></i> Vacinações
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'aniversariantes' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => $bdayBucket]) }}">
                    <i class="fas fa-birthday-cake mr-1"></i> Aniversariantes (Pets)
                    @if(($stats['birthdays_today'] ?? 0) > 0)
                        <span class="badge ml-1" style="background-color: #e83e8c; color: #fff;">{{ $stats['birthdays_today'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'banho_tosa' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => $groomBucket]) }}">
                    <i class="fas fa-bath mr-1"></i> Banho e Tosa
                    @if(($stats['grooming_today'] ?? 0) > 0)
                        <span class="badge badge-info ml-1">{{ $stats['grooming_today'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'mensagens' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'mensagens']) }}">
                    <i class="fab fa-whatsapp mr-1"></i> Histórico e Fila
                    @if($stats['messages_failed'] > 0)
                        <span class="badge badge-danger ml-1">{{ $stats['messages_failed'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tutores' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'tutores']) }}">
                    <i class="fas fa-user-shield mr-1"></i> Tutores (LGPD)
                </a>
            </li>
            @if($canManageSettings)
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'configuracoes' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'configuracoes']) }}">
                    <i class="fas fa-cogs mr-1"></i> Configurações e Modelos
                </a>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        {{-- ================= ABA 1: ACOMPANHAMENTO DE VACINAS ================= --}}
        @if($tab === 'acompanhamento')
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'due_today']) }}" class="btn {{ $bucket === 'due_today' ? 'btn-warning' : 'btn-outline-secondary' }}">Hoje ({{ $stats['due_today'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'overdue']) }}" class="btn {{ $bucket === 'overdue' ? 'btn-danger' : 'btn-outline-secondary' }}">Em Atraso ({{ $stats['overdue'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_7_days']) }}" class="btn {{ $bucket === 'next_7_days' ? 'btn-info' : 'btn-outline-secondary' }}">Próximos 7 dias ({{ $stats['next_7_days'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_30_days']) }}" class="btn {{ $bucket === 'next_30_days' ? 'btn-primary' : 'btn-outline-secondary' }}">Próximos 30 dias ({{ $stats['next_30_days'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'completed']) }}" class="btn {{ $bucket === 'completed' ? 'btn-success' : 'btn-outline-secondary' }}">Concluídas</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="acompanhamento">
                    <input type="hidden" name="bucket" value="{{ $bucket }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar animal, tutor, vacina, lab...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Animal</th>
                            <th>Tutor / Telefone</th>
                            <th>Vacina / Laboratório</th>
                            <th>Última Aplicação</th>
                            <th>Próxima Prevista</th>
                            <th>Situação</th>
                            <th>Contato / WhatsApp</th>
                            <th style="width: 240px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vaccinations as $vac)
                        @php
                            $tutor = $vac->pet?->tutors?->first();
                            $lastRem = $vac->reminders->first();
                        @endphp
                        <tr>
                            <td>
                                @if($vac->pet)
                                    <a href="{{ route('pets.show', $vac->pet) }}"><strong>{{ $vac->pet->name }}</strong></a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($tutor)
                                    <a href="{{ route('tutors.show', $tutor) }}">{{ $tutor->name }}</a><br>
                                    <small class="{{ $tutor->hasValidWhatsAppPhone() ? 'text-muted' : 'text-danger' }}">
                                        <i class="fas fa-phone-alt mr-1"></i>{{ $tutor->phone ?: 'Sem telefone' }}
                                    </small>
                                    @if($tutor->whatsapp_opt_in === true)
                                        <span class="badge badge-success ml-1">Opt-in</span>
                                    @elseif($tutor->whatsapp_opt_in === false)
                                        <span class="badge badge-danger ml-1">Opt-out</span>
                                    @else
                                        <span class="badge badge-light border ml-1">Não conf.</span>
                                    @endif
                                @else
                                    <span class="text-danger small">Sem tutor</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $vac->vaccine }}</strong>
                                @if($vac->laboratory)
                                    <br><small class="text-muted">{{ $vac->laboratory }}</small>
                                @endif
                            </td>
                            <td>{{ $vac->date ? $vac->date->format('d/m/Y') : '-' }}</td>
                            <td><strong>{{ $vac->next_date ? $vac->next_date->format('d/m/Y') : '-' }}</strong></td>
                            <td><span class="badge badge-{{ $vac->classification_badge }}">{{ $vac->classification_label }}</span></td>
                            <td>
                                @if($lastRem)
                                    <span class="badge badge-{{ $lastRem->status_badge }}">{{ $lastRem->status_label }}</span>
                                    <small class="d-block text-muted">{{ $lastRem->type_label }} — {{ ($lastRem->sent_at ?? $lastRem->created_at)?->format('d/m/Y H:i') }}</small>
                                @elseif($vac->last_contact_at)
                                    <span class="badge badge-info">Contatado</span>
                                    <small class="d-block text-muted">{{ $vac->last_contact_at->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="badge badge-light border">Sem contato</span>
                                @endif
                                @if($vac->last_contact_notes)
                                    <small class="d-block text-muted text-truncate" style="max-width: 180px;" title="{{ $vac->last_contact_notes }}">{{ $vac->last_contact_notes }}</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($vac->pet)
                                    <a href="{{ route('vaccinations.index', ['pet_id' => $vac->pet_id]) }}" class="btn btn-xs btn-secondary" title="Histórico de Vacinação"><i class="fas fa-history"></i></a>
                                    <a href="{{ route('pets.show', $vac->pet) }}" class="btn btn-xs btn-info" title="Cadastro do Animal"><i class="fas fa-paw"></i></a>
                                @endif
                                @if($tutor)
                                    <a href="{{ route('tutors.show', $tutor) }}" class="btn btn-xs btn-outline-info" title="Cadastro do Tutor"><i class="fas fa-user"></i></a>
                                    @if($vac->followup_status === 'pending')
                                        <button type="button" onclick="openWhatsAppPreviewModal({{ $vac->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar WhatsApp"><i class="fab fa-whatsapp"></i></button>
                                    @endif
                                @endif
                                @if($vac->followup_status === 'pending')
                                    <button type="button" onclick="openRegisterContactModal({{ $vac->id }}, '{{ addslashes(($vac->pet->name ?? '') . ' - ' . $vac->vaccine) }}', '{{ addslashes((string) ($vac->last_contact_notes ?? '')) }}')" class="btn btn-xs btn-outline-secondary" title="Registrar Contato"><i class="fas fa-phone-alt"></i></button>
                                    <a href="{{ route('vaccinations.create', ['pet_id' => $vac->pet_id, 'complete_vaccination_id' => $vac->id]) }}" class="btn btn-xs btn-primary" title="Registrar Vacinação Aplicada"><i class="fas fa-check"></i></a>
                                    <form action="{{ route('vaccination-center.cancel-pending', $vac) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancelar esta pendência e todos os lembretes futuros dela?')">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Cancelar Pendência"><i class="fas fa-ban"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhum registro encontrado nesta categoria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $vaccinations->withQueryString()->links() }}</div>

        {{-- ================= ABA: ANIVERSARIANTES DO DIA / MES (PETS) ================= --}}
        @elseif($tab === 'aniversariantes')
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'today']) }}" class="btn {{ $bdayBucket === 'today' ? 'btn-danger' : 'btn-outline-secondary' }}">Hoje ({{ $stats['birthdays_today'] ?? 0 }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'next_7_days']) }}" class="btn {{ $bdayBucket === 'next_7_days' ? 'btn-info' : 'btn-outline-secondary' }}">Próximos 7 Dias</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'month']) }}" class="btn {{ $bdayBucket === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">Neste Mês ({{ $stats['birthdays_month'] ?? 0 }})</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="aniversariantes">
                    <input type="hidden" name="bday_bucket" value="{{ $bdayBucket }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar pet, raça, tutor...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Pet / Espécie / Raça</th>
                            <th>Data de Nascimento</th>
                            <th>Idade</th>
                            <th>Tutor / Telefone</th>
                            <th>Autorização LGPD</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($birthdays as $pet)
                        @php
                            $tutor = $pet->tutors->first();
                            $ageYears = $pet->birth_date ? (int) $pet->birth_date->diffInYears(now()) : null;
                            $isTodayBday = $pet->birth_date && $pet->birth_date->format('m-d') === now()->format('m-d');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('pets.show', $pet) }}"><strong>{{ $pet->name }}</strong></a>
                                @if($isTodayBday)
                                    <span class="badge ml-1" style="background-color: #e83e8c; color: #fff;">🎂 Hoje!</span>
                                @endif
                                <br><small class="text-muted">{{ ucfirst($pet->species ?? '') }} {{ $pet->breed ? '• ' . $pet->breed : '' }}</small>
                            </td>
                            <td><strong>{{ $pet->birth_date ? $pet->birth_date->format('d/m/Y') : '-' }}</strong></td>
                            <td>{{ $ageYears !== null ? ($ageYears <= 0 ? 'Menos de 1 ano' : ($ageYears === 1 ? '1 ano' : "{$ageYears} anos")) : '-' }}</td>
                            <td>
                                @if($tutor)
                                    <a href="{{ route('tutors.show', $tutor) }}">{{ $tutor->name }}</a><br>
                                    <small class="{{ $tutor->hasValidWhatsAppPhone() ? 'text-muted' : 'text-danger' }}">
                                        <i class="fas fa-phone-alt mr-1"></i>{{ $tutor->phone ?: 'Sem telefone' }}
                                    </small>
                                @else
                                    <span class="text-danger small">Sem tutor vinculado</span>
                                @endif
                            </td>
                            <td>
                                @if($tutor)
                                    @if($tutor->whatsapp_opt_in === true)
                                        <span class="badge badge-success">Autorizado</span>
                                    @elseif($tutor->whatsapp_opt_in === false)
                                        <span class="badge badge-danger">Recusado</span>
                                    @else
                                        <span class="badge badge-warning">Não confirmado</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('pets.show', $pet) }}" class="btn btn-xs btn-info" title="Cadastro do Animal"><i class="fas fa-paw"></i></a>
                                @if($tutor)
                                    <a href="{{ route('tutors.show', $tutor) }}" class="btn btn-xs btn-outline-info" title="Cadastro do Tutor"><i class="fas fa-user"></i></a>
                                    <button type="button" onclick="openBirthdayWhatsAppModal({{ $pet->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar Parabéns via WhatsApp">
                                        <i class="fab fa-whatsapp mr-1"></i>Enviar
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum pet aniversariante encontrado neste período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $birthdays->withQueryString()->links() }}</div>

        {{-- ================= ABA: AGENDAMENTOS DE BANHO E TOSA ================= --}}
        @elseif($tab === 'banho_tosa')
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'today']) }}" class="btn {{ $groomBucket === 'today' ? 'btn-info' : 'btn-outline-secondary' }}">Hoje ({{ $stats['grooming_today'] ?? 0 }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'tomorrow']) }}" class="btn {{ $groomBucket === 'tomorrow' ? 'btn-primary' : 'btn-outline-secondary' }}">Amanhã</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'next_7_days']) }}" class="btn {{ $groomBucket === 'next_7_days' ? 'btn-teal' : 'btn-outline-secondary' }}">Próximos 7 Dias ({{ $stats['grooming_next_7_days'] ?? 0 }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'all']) }}" class="btn {{ $groomBucket === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Todos Futuros</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="banho_tosa">
                    <input type="hidden" name="groom_bucket" value="{{ $groomBucket }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar pet, tutor, serviço...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Data / Horário</th>
                            <th>Animal</th>
                            <th>Tutor / Telefone</th>
                            <th>Serviço / Profissional</th>
                            <th>Status</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groomings as $appt)
                        @php
                            $tutor = $appt->pet?->tutors?->first();
                            $svcNames = $appt->services->map(fn($s) => $s->service?->name)->filter()->implode(', ') ?: ($appt->reason ?: 'Banho e Tosa');
                            $hora = $appt->time ? (is_string($appt->time) ? substr($appt->time, 0, 5) : $appt->time->format('H:i')) : '--:--';
                            $statusLabels = [
                                'scheduled' => ['Agendado', 'primary'],
                                'checked_in' => ['Chegou', 'info'],
                                'in_progress' => ['Em Atendimento', 'warning'],
                                'completed' => ['Pronto', 'success'],
                                'delivered' => ['Entregue', 'secondary'],
                                'cancelled' => ['Cancelado', 'danger'],
                            ];
                            $st = $statusLabels[$appt->status] ?? [ucfirst($appt->status ?? ''), 'light'];
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $appt->date ? $appt->date->format('d/m/Y') : '-' }}</strong>
                                <br><span class="badge badge-info">{{ $hora }}</span>
                            </td>
                            <td>
                                @if($appt->pet)
                                    <a href="{{ route('pets.show', $appt->pet) }}"><strong>{{ $appt->pet->name }}</strong></a>
                                    <br><small class="text-muted">{{ $appt->pet->breed ?? '' }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($tutor)
                                    <a href="{{ route('tutors.show', $tutor) }}">{{ $tutor->name }}</a><br>
                                    <small class="{{ $tutor->hasValidWhatsAppPhone() ? 'text-muted' : 'text-danger' }}">
                                        <i class="fas fa-phone-alt mr-1"></i>{{ $tutor->phone ?: 'Sem telefone' }}
                                    </small>
                                    @if($tutor->whatsapp_opt_in === true)
                                        <span class="badge badge-success ml-1">Opt-in</span>
                                    @elseif($tutor->whatsapp_opt_in === false)
                                        <span class="badge badge-danger ml-1">Opt-out</span>
                                    @endif
                                @else
                                    <span class="text-danger small">Sem tutor</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $svcNames }}</strong>
                                @if($appt->groomer)
                                    <br><small class="text-muted"><i class="fas fa-user-tag mr-1"></i>{{ $appt->groomer->name }}</small>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $st[1] }}">{{ $st[0] }}</span></td>
                            <td class="text-nowrap">
                                @if($appt->pet)
                                    <a href="{{ route('pets.show', $appt->pet) }}" class="btn btn-xs btn-info" title="Cadastro do Animal"><i class="fas fa-paw"></i></a>
                                @endif
                                @if($tutor)
                                    <button type="button" onclick="openGroomingWhatsAppModal({{ $appt->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar Lembrete de Banho e Tosa via WhatsApp">
                                        <i class="fab fa-whatsapp mr-1"></i>Enviar
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum agendamento de Banho e Tosa encontrado neste período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $groomings->withQueryString()->links() }}</div>

        {{-- ================= ABA: HISTORICO E FILA DE MENSAGENS ================= --}}
        @elseif($tab === 'mensagens')
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => $msgStatus, 'msg_category' => 'all']) }}" class="btn {{ $msgCategory === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">Todas Categorias</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => $msgStatus, 'msg_category' => 'vaccination']) }}" class="btn {{ $msgCategory === 'vaccination' ? 'btn-success' : 'btn-outline-secondary' }}"><i class="fas fa-syringe mr-1"></i>Vacinações</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => $msgStatus, 'msg_category' => 'birthday']) }}" class="btn {{ $msgCategory === 'birthday' ? 'btn-danger' : 'btn-outline-secondary' }}"><i class="fas fa-birthday-cake mr-1"></i>Aniversariantes</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => $msgStatus, 'msg_category' => 'grooming']) }}" class="btn {{ $msgCategory === 'grooming' ? 'btn-info' : 'btn-outline-secondary' }}"><i class="fas fa-bath mr-1"></i>Banho e Tosa</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="mensagens">
                    <input type="hidden" name="msg_status" value="{{ $msgStatus }}">
                    <input type="hidden" name="msg_category" value="{{ $msgCategory }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar pet, tutor, destino...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="btn-group btn-group-sm mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'all', 'msg_category' => $msgCategory]) }}" class="btn {{ $msgStatus === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Todos Status</a>
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'sent', 'msg_category' => $msgCategory]) }}" class="btn {{ $msgStatus === 'sent' ? 'btn-success' : 'btn-outline-secondary' }}">Enviadas ({{ $stats['messages_sent'] }})</a>
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'pending', 'msg_category' => $msgCategory]) }}" class="btn {{ $msgStatus === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">Aguardando ({{ $stats['messages_pending'] }})</a>
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'failed', 'msg_category' => $msgCategory]) }}" class="btn {{ $msgStatus === 'failed' ? 'btn-danger' : 'btn-outline-secondary' }}">Com Falha ({{ $stats['messages_failed'] }})</a>
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'cancelled', 'msg_category' => $msgCategory]) }}" class="btn {{ $msgStatus === 'cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Canceladas ({{ $stats['messages_cancelled'] }})</a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Categoria / Animal</th>
                            <th>Tutor / Destino</th>
                            <th>Etapa / Ref.</th>
                            <th>Status</th>
                            <th>Provedor / Datas</th>
                            <th>Detalhe / Erro</th>
                            <th style="width: 105px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reminders as $rem)
                        <tr>
                            <td>{{ $rem->id }}</td>
                            <td>
                                <span class="badge badge-{{ $rem->category === 'birthday' ? 'danger' : ($rem->category === 'grooming' ? 'info' : 'success') }}">{{ $rem->category_label }}</span><br>
                                <strong>{{ $rem->pet->name ?? '-' }}</strong>
                                @if($rem->vaccination)
                                    <br><small class="text-muted">{{ $rem->vaccination->vaccine }}</small>
                                @elseif($rem->appointment)
                                    <br><small class="text-muted">{{ $rem->appointment->reason ?: 'Banho e Tosa' }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $rem->tutor->name ?? '-' }}<br>
                                <small class="text-muted">{{ $rem->destination ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-light border">{{ $rem->type_label }}</span><br>
                                <small class="text-muted">Ref.: {{ $rem->reference_date ? $rem->reference_date->format('d/m/Y') : '-' }}</small>
                            </td>
                            <td><span class="badge badge-{{ $rem->status_badge }}">{{ $rem->status_label }}</span></td>
                            <td>
                                <small>
                                    {{ $rem->provider ?: '-' }}<br>
                                    @if($rem->sent_at) Env: {{ $rem->sent_at->format('d/m/Y H:i') }}<br> @endif
                                    @if($rem->delivered_at) Entr: {{ $rem->delivered_at->format('d/m H:i') }}<br> @endif
                                    @if($rem->read_at) Lida: {{ $rem->read_at->format('d/m H:i') }} @endif
                                </small>
                            </td>
                            <td>
                                @if($rem->error_message)
                                    <span class="text-danger small">{{ $rem->error_message }}</span>
                                @elseif($rem->cancelled_reason)
                                    <span class="text-muted small">{{ $rem->cancelled_reason }}</span>
                                @else
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 220px;" title="{{ $rem->message_body }}">{{ $rem->message_body }}</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if(in_array($rem->status, ['failed', 'prepared', 'pending']))
                                    <form action="{{ route('vaccination-center.reprocess', $rem) }}" method="POST" class="d-inline" onsubmit="return confirm('Reprocessar e enviar esta mensagem agora via WhatsApp?')">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-warning" title="Reprocessar / Enviar Agora">
                                            <i class="fas fa-redo mr-1"></i>Reenviar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma mensagem registrada com este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reminders->withQueryString()->links() }}</div>

        {{-- ================= ABA 3: TUTORES (TELEFONE E OPT-IN LGPD) ================= --}}
        @elseif($tab === 'tutores')
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'tutores', 'tutor_filter' => 'invalid_phone']) }}" class="btn {{ $tutorFilter === 'invalid_phone' ? 'btn-danger' : 'btn-outline-secondary' }}">Sem Telefone Válido</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'tutores', 'tutor_filter' => 'no_opt_in']) }}" class="btn {{ $tutorFilter === 'no_opt_in' ? 'btn-warning' : 'btn-outline-secondary' }}">Sem Autorização (Não Conf.)</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'tutores', 'tutor_filter' => 'opt_out']) }}" class="btn {{ $tutorFilter === 'opt_out' ? 'btn-dark' : 'btn-outline-secondary' }}">Recusaram (Opt-out)</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'tutores', 'tutor_filter' => 'authorized']) }}" class="btn {{ $tutorFilter === 'authorized' ? 'btn-success' : 'btn-outline-secondary' }}">Autorizados (Opt-in)</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="tutores">
                    <input type="hidden" name="tutor_filter" value="{{ $tutorFilter }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar tutor, CPF, telefone...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Tutor</th>
                            <th>Telefone Principal</th>
                            <th>Telefone Secundário</th>
                            <th>Pets</th>
                            <th>Autorização WhatsApp (LGPD)</th>
                            <th style="width: 240px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tutors as $t)
                        <tr>
                            <td><a href="{{ route('tutors.show', $t) }}"><strong>{{ $t->name }}</strong></a></td>
                            <td class="{{ $t->hasValidWhatsAppPhone() ? '' : 'text-danger' }}">{{ $t->phone ?: 'Não informado' }}</td>
                            <td>{{ $t->phone_secondary ?: '-' }}</td>
                            <td>{{ $t->pets_count }}</td>
                            <td>
                                @if($t->whatsapp_opt_in === true)
                                    <span class="badge badge-success">Autorizado</span>
                                    @if($t->whatsapp_opt_in_at)<small class="text-muted ml-1">{{ $t->whatsapp_opt_in_at->format('d/m/Y') }}</small>@endif
                                @elseif($t->whatsapp_opt_in === false)
                                    <span class="badge badge-danger">Recusado (Opt-out)</span>
                                @else
                                    <span class="badge badge-warning">Não confirmado</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('tutors.show', $t) }}" class="btn btn-xs btn-info" title="Abrir Tutor"><i class="fas fa-user"></i></a>
                                @if($t->whatsapp_opt_in !== true)
                                    <form action="{{ route('vaccination-center.tutor-opt-in', $t) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="opt_in" value="1">
                                        <button type="submit" class="btn btn-xs btn-success" title="Registrar Autorização (Opt-in)"><i class="fas fa-check mr-1"></i>Autorizar</button>
                                    </form>
                                @endif
                                @if($t->whatsapp_opt_in !== false)
                                    <form action="{{ route('vaccination-center.tutor-opt-in', $t) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="opt_in" value="0">
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Registrar Recusa (Opt-out)"><i class="fas fa-ban mr-1"></i>Recusar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum tutor encontrado com este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $tutors->withQueryString()->links() }}</div>

        {{-- ================= ABA: CONFIGURACOES E MODELOS (ADMIN) ================= --}}
        @elseif($tab === 'configuracoes' && $canManageSettings)
            <form action="{{ route('vaccination-center.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-sliders-h mr-1"></i>Serviços Ativos e Regras de Envio Automático</strong></div>
                            <div class="card-body">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" class="custom-control-input" id="autoSendSwitch" name="auto_send_enabled" value="1" {{ $settings['auto_send_enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-success" for="autoSendSwitch">Ativar envio automático diário de WhatsApp</label>
                                    <small class="form-text text-muted">Quando desativado, a rotina diária apenas identifica e prepara lembretes sem disparar mensagens automaticamente.</small>
                                </div>

                                <div class="form-group">
                                    <label>Horário da Rotina Diária (America/Sao_Paulo)</label>
                                    <input type="time" name="daily_run_time" value="{{ $settings['daily_run_time'] }}" class="form-control" style="max-width: 180px;" required>
                                </div>

                                <hr>

                                {{-- SERVICO 1: VACINACOES --}}
                                <div class="p-3 mb-3 rounded border bg-white">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="enableVacCheck" name="enable_vaccinations" value="1" {{ $settings['enable_vaccinations'] ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-dark" for="enableVacCheck">
                                            <i class="fas fa-syringe text-success mr-1"></i> Notificar Vacinações
                                        </label>
                                    </div>
                                    <div class="pl-4">
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="rem7Switch" name="remind_7_days_enabled" value="1" {{ $settings['remind_7_days_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="rem7Switch">Ativar lembrete de 7 dias antes</label>
                                        </div>
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="rem2Switch" name="remind_2_days_enabled" value="1" {{ $settings['remind_2_days_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="rem2Switch">Ativar lembrete de 2 dias antes</label>
                                        </div>
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="remTodaySwitch" name="remind_due_today_enabled" value="1" {{ $settings['remind_due_today_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="remTodaySwitch">Ativar lembrete no dia previsto</label>
                                        </div>
                                        <div class="custom-control custom-switch mb-3">
                                            <input type="checkbox" class="custom-control-input" id="remOverdueSwitch" name="remind_overdue_enabled" value="1" {{ $settings['remind_overdue_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="remOverdueSwitch">Ativar lembretes de vacinação em atraso</label>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label class="small">Intervalo Atraso (dias)</label>
                                                    <input type="number" name="overdue_interval_days" value="{{ $settings['overdue_interval_days'] }}" min="1" max="90" class="form-control form-control-sm" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label class="small">Máx. Tentativas</label>
                                                    <input type="number" name="max_overdue_attempts" value="{{ $settings['max_overdue_attempts'] }}" min="1" max="20" class="form-control form-control-sm" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label class="small">Janela Máx. (dias)</label>
                                                    <input type="number" name="overdue_max_age_days" value="{{ $settings['overdue_max_age_days'] }}" min="7" max="1825" class="form-control form-control-sm" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SERVICO 2: ANIVERSARIANTES DO DIA (PETS) --}}
                                <div class="p-3 mb-3 rounded border bg-white">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enableBdayCheck" name="enable_birthdays" value="1" {{ $settings['enable_birthdays'] ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-dark" for="enableBdayCheck">
                                            <i class="fas fa-birthday-cake mr-1" style="color: #e83e8c;"></i> Notificar Aniversariantes do Dia (Pets)
                                        </label>
                                        <small class="form-text text-muted pl-4">Envia automaticamente uma mensagem carinhosa de parabéns ao tutor no dia do aniversário do pet (com proteção contra duplicidade anual).</small>
                                    </div>
                                </div>

                                {{-- SERVICO 3: AGENDAMENTOS DE BANHO E TOSA --}}
                                <div class="p-3 rounded border bg-white">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="enableGroomCheck" name="enable_grooming" value="1" {{ $settings['enable_grooming'] ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold text-dark" for="enableGroomCheck">
                                            <i class="fas fa-bath text-teal mr-1"></i> Notificar Agendamentos de Banho e Tosa
                                        </label>
                                    </div>
                                    <div class="pl-4">
                                        <div class="custom-control custom-switch mb-2">
                                            <input type="checkbox" class="custom-control-input" id="groom1DaySwitch" name="grooming_remind_1_day_enabled" value="1" {{ $settings['grooming_remind_1_day_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="groom1DaySwitch">Ativar lembrete 1 dia antes do agendamento</label>
                                        </div>
                                        <div class="custom-control custom-switch mb-0">
                                            <input type="checkbox" class="custom-control-input" id="groomTodaySwitch" name="grooming_remind_today_enabled" value="1" {{ $settings['grooming_remind_today_enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="groomTodaySwitch">Ativar lembrete no dia do agendamento</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-robot mr-1"></i>Rotina Agendada & Webhook</strong></div>
                            <div class="card-body small">
                                <p class="mb-1"><strong>Comando CRON (Linux):</strong></p>
                                <code class="d-block p-2 bg-dark text-light rounded mb-2">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
                                <p class="mb-1"><strong>Windows (Agendador de Tarefas):</strong> execute <code>installer\instalar-agendador-windows.bat</code> como Administrador.</p>
                                <p class="mb-1"><strong>Webhook de Entrega/Leitura (opcional):</strong></p>
                                <code class="d-block p-2 bg-light border rounded">POST {{ url('/api/whatsapp/webhook/{zapi|cloudapi|twilio}') }}</code>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-syringe text-success mr-1"></i>Modelos de Mensagens — Vacinações</strong></div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    Variáveis disponíveis:
                                    <code>{NOME_TUTOR}</code> <code>{NOME_ANIMAL}</code> <code>{NOME_VACINA}</code> <code>{DATA_VACINACAO}</code> <code>{CLINICA}</code> <code>{LABORATORIO}</code> <code>{DATA_APLICACAO}</code>
                                </p>
                                <div class="form-group">
                                    <label>1. Vacinação — 7 Dias Antes</label>
                                    <textarea name="template_7_days" rows="4" class="form-control" required>{{ $settings['template_7_days'] }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>2. Vacinação — 2 Dias Antes</label>
                                    <textarea name="template_2_days" rows="4" class="form-control" required>{{ $settings['template_2_days'] }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>3. Vacinação — No Dia Previsto</label>
                                    <textarea name="template_due_today" rows="4" class="form-control" required>{{ $settings['template_due_today'] }}</textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label>4. Vacinação — Em Atraso</label>
                                    <textarea name="template_overdue" rows="4" class="form-control" required>{{ $settings['template_overdue'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-birthday-cake mr-1" style="color: #e83e8c;"></i>Modelo de Mensagem — Aniversariante do Dia (Pet)</strong></div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    Variáveis disponíveis:
                                    <code>{NOME_TUTOR}</code> <code>{NOME_ANIMAL}</code> <code>{DATA_NASCIMENTO}</code> <code>{IDADE_ANIMAL}</code> <code>{CLINICA}</code>
                                </p>
                                <div class="form-group mb-0">
                                    <label>5. Felicitação de Aniversário (Pet)</label>
                                    <textarea name="template_birthday" rows="5" class="form-control" required>{{ $settings['template_birthday'] }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-bath text-teal mr-1"></i>Modelo de Mensagem — Agendamento de Banho e Tosa</strong></div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    Variáveis disponíveis:
                                    <code>{NOME_TUTOR}</code> <code>{NOME_ANIMAL}</code> <code>{DATA_AGENDAMENTO}</code> <code>{HORARIO_AGENDAMENTO}</code> <code>{SERVICO_BANHO_TOSA}</code> <code>{PROFISSIONAL}</code> <code>{CLINICA}</code>
                                </p>
                                <div class="form-group mb-0">
                                    <label>6. Lembrete de Banho e Tosa</label>
                                    <textarea name="template_grooming" rows="5" class="form-control" required>{{ $settings['template_grooming'] }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap">
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Salvar Configurações e Modelos</button>
                    </div>
                </div>
            </form>

            <hr>
            <div class="d-flex align-items-center flex-wrap">
                <form action="{{ route('vaccination-center.run-now') }}" method="POST" class="mr-2 mb-2">
                    @csrf
                    <input type="hidden" name="dry_run" value="1">
                    <button type="submit" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-vial mr-1"></i> Simular Rotina Diária (Dry-Run)
                    </button>
                </form>
                <form action="{{ route('vaccination-center.run-now') }}" method="POST" class="mb-2" onsubmit="return confirm('Executar a rotina de preparação/envio agora conforme as configurações atuais?')">
                    @csrf
                    <input type="hidden" name="dry_run" value="0">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-play mr-1"></i> Executar Rotina Diária Agora
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

@include('vaccination-center._modals')
@endsection
