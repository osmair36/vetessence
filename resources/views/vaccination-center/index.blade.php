@extends('layouts.adminlte', ['title' => 'Central de Lembretes e Notificações de Vacinação'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <div>
        <h4 class="mb-0"><i class="fab fa-whatsapp text-success mr-2"></i>Central de Lembretes e Notificações de Vacinação</h4>
        <small class="text-muted">
            Provedor WhatsApp: <strong>{{ $stats['whatsapp_provider'] ? strtoupper($stats['whatsapp_provider']) : 'Não configurado' }}</strong>
            | Envio Automático:
            @if($stats['auto_send_enabled'])
                <span class="badge badge-success">ATIVADO ({{ $settings['daily_run_time'] }} America/Sao_Paulo)</span>
            @else
                <span class="badge badge-secondary">DESATIVADO</span>
            @endif
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

{{-- 7 Indicadores Clicaveis no Topo --}}
<div class="row mb-3">
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'due_today']) }}" class="text-decoration-none">
            <div class="info-box bg-warning mb-0 {{ $tab === 'acompanhamento' && $bucket === 'due_today' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Vencem Hoje</span>
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
                    <span class="info-box-text">Em Atraso</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['overdue'] }}</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_7_days']) }}" class="text-decoration-none">
            <div class="info-box bg-info mb-0 {{ $tab === 'acompanhamento' && $bucket === 'next_7_days' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Próximos 7 Dias</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['next_7_days'] }}</span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-6 mb-2">
        <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_30_days']) }}" class="text-decoration-none">
            <div class="info-box bg-primary mb-0 {{ $tab === 'acompanhamento' && $bucket === 'next_30_days' ? 'elevation-3' : '' }}">
                <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Próximos 30 Dias</span>
                    <span class="info-box-number h5 mb-0">{{ $stats['next_30_days'] }}</span>
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
                    <i class="fas fa-syringe mr-1"></i> Acompanhamento de Vacinas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'mensagens' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'mensagens']) }}">
                    <i class="fab fa-whatsapp mr-1"></i> Histórico e Fila de Mensagens
                    @if($stats['messages_failed'] > 0)
                        <span class="badge badge-danger ml-1">{{ $stats['messages_failed'] }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tutores' ? 'active' : '' }}" href="{{ route('vaccination-center.index', ['tab' => 'tutores']) }}">
                    <i class="fas fa-user-shield mr-1"></i> Tutores (Telefone / Autorização LGPD)
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

        {{-- ================= ABA 2: HISTORICO E FILA DE MENSAGENS ================= --}}
        @elseif($tab === 'mensagens')
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div class="btn-group btn-group-sm mb-2">
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'all']) }}" class="btn {{ $msgStatus === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Todas</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'sent']) }}" class="btn {{ $msgStatus === 'sent' ? 'btn-success' : 'btn-outline-secondary' }}">Enviadas ({{ $stats['messages_sent'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'pending']) }}" class="btn {{ $msgStatus === 'pending' ? 'btn-warning' : 'btn-outline-secondary' }}">Aguardando ({{ $stats['messages_pending'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'failed']) }}" class="btn {{ $msgStatus === 'failed' ? 'btn-danger' : 'btn-outline-secondary' }}">Com Falha ({{ $stats['messages_failed'] }})</a>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'cancelled']) }}" class="btn {{ $msgStatus === 'cancelled' ? 'btn-secondary' : 'btn-outline-secondary' }}">Canceladas ({{ $stats['messages_cancelled'] }})</a>
                </div>
                <form method="GET" action="{{ route('vaccination-center.index') }}" class="form-inline mb-2">
                    <input type="hidden" name="tab" value="mensagens">
                    <input type="hidden" name="msg_status" value="{{ $msgStatus }}">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm mr-1" placeholder="Buscar pet, tutor, destino...">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Animal / Vacina</th>
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
                                <strong>{{ $rem->pet->name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $rem->vaccination->vaccine ?? '-' }}</small>
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

        {{-- ================= ABA 4: CONFIGURACOES E MODELOS (ADMIN) ================= --}}
        @elseif($tab === 'configuracoes' && $canManageSettings)
            <form action="{{ route('vaccination-center.settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-light border">
                            <div class="card-header"><strong><i class="fas fa-sliders-h mr-1"></i>Regras de Envio Automático</strong></div>
                            <div class="card-body">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" class="custom-control-input" id="autoSendSwitch" name="auto_send_enabled" value="1" {{ $settings['auto_send_enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-success" for="autoSendSwitch">Ativar envio automático diário de WhatsApp</label>
                                    <small class="form-text text-muted">Quando desativado, a rotina diária apenas identifica e prepara lembretes sem disparar mensagens automaticamente.</small>
                                </div>
                                <hr>
                                <div class="custom-control custom-switch mb-2">
                                    <input type="checkbox" class="custom-control-input" id="rem7Switch" name="remind_7_days_enabled" value="1" {{ $settings['remind_7_days_enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="rem7Switch">Ativar lembrete de 7 dias antes</label>
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Horário Diário (America/Sao_Paulo)</label>
                                            <input type="time" name="daily_run_time" value="{{ $settings['daily_run_time'] }}" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Intervalo de Atraso (dias)</label>
                                            <input type="number" name="overdue_interval_days" value="{{ $settings['overdue_interval_days'] }}" min="1" max="90" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label>Limite de Tentativas (atraso)</label>
                                            <input type="number" name="max_overdue_attempts" value="{{ $settings['max_overdue_attempts'] }}" min="1" max="20" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label>Janela Máx. Atraso Automático (dias)</label>
                                            <input type="number" name="overdue_max_age_days" value="{{ $settings['overdue_max_age_days'] }}" min="7" max="1825" class="form-control" required>
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
                            <div class="card-header"><strong><i class="fas fa-comment-dots mr-1"></i>Modelos de Mensagens Editáveis</strong></div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    Variáveis disponíveis:
                                    <code>{NOME_TUTOR}</code> <code>{NOME_ANIMAL}</code> <code>{NOME_VACINA}</code> <code>{DATA_VACINACAO}</code> <code>{CLINICA}</code> <code>{LABORATORIO}</code> <code>{DATA_APLICACAO}</code>
                                </p>
                                <div class="form-group">
                                    <label>1. Modelo — 7 Dias Antes</label>
                                    <textarea name="template_7_days" rows="6" class="form-control" required>{{ $settings['template_7_days'] }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>2. Modelo — No Dia Previsto</label>
                                    <textarea name="template_due_today" rows="6" class="form-control" required>{{ $settings['template_due_today'] }}</textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label>3. Modelo — Vacinação em Atraso</label>
                                    <textarea name="template_overdue" rows="6" class="form-control" required>{{ $settings['template_overdue'] }}</textarea>
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
