@can('lembrete-vacinas')
@php
    $vs = $vaccineStats ?? [];
    $todayList = $todayVaccinations ?? collect();
@endphp
<div class="card card-outline card-success mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">
            <i class="fas fa-syringe text-success mr-2"></i>
            <strong>Acompanhamento de Vacinações e Lembretes WhatsApp</strong>
            @if(!empty($vs['auto_send_enabled']))
                <span class="badge badge-success ml-2"><i class="fab fa-whatsapp mr-1"></i>Envio Automático Ativo</span>
            @else
                <span class="badge badge-secondary ml-2"><i class="fas fa-pause-circle mr-1"></i>Envio Automático Desativado</span>
            @endif
        </h3>
        <div class="card-tools">
            <a href="{{ route('vaccination-center.index') }}" class="btn btn-sm btn-success">
                <i class="fab fa-whatsapp mr-1"></i> Abrir Central de Lembretes
            </a>
        </div>
    </div>
    <div class="card-body">
        {{-- 7 Indicadores Clicaveis --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'due_today']) }}" class="text-decoration-none">
                    <div class="info-box bg-warning mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Vacinas Previstas Hoje</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['due_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'overdue']) }}" class="text-decoration-none">
                    <div class="info-box bg-danger mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Vacinas em Atraso</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['overdue'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_7_days']) }}" class="text-decoration-none">
                    <div class="info-box bg-info mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-calendar-week"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Próximos 7 Dias</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['next_7_days'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'next_30_days']) }}" class="text-decoration-none">
                    <div class="info-box bg-primary mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Próximos 30 Dias</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['next_30_days'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 col-12 mb-2">
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'sent']) }}" class="text-decoration-none">
                    <div class="info-box mb-0 border">
                        <span class="info-box-icon bg-success"><i class="fab fa-whatsapp"></i></span>
                        <div class="info-box-content text-dark">
                            <span class="info-box-text">WhatsApp Enviadas</span>
                            <span class="info-box-number">{{ $vs['messages_sent'] ?? 0 }}</span>
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
                            <span class="info-box-number">{{ $vs['messages_pending'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-12 mb-2">
                <a href="{{ route('vaccination-center.index', ['tab' => 'mensagens', 'msg_status' => 'failed']) }}" class="text-decoration-none">
                    <div class="info-box mb-0 border">
                        <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                        <div class="info-box-content text-dark">
                            <span class="info-box-text">Falhas de Envio</span>
                            <span class="info-box-number">{{ $vs['messages_failed'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Lista de vacinacoes previstas para HOJE --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fas fa-list-ul text-warning mr-1"></i> Vacinações Previstas para Hoje ({{ now()->format('d/m/Y') }})</h5>
            <a href="{{ route('vaccination-center.index', ['tab' => 'acompanhamento', 'bucket' => 'due_today']) }}" class="btn btn-xs btn-outline-primary">
                Ver lista completa
            </a>
        </div>

        @if($todayList->count() > 0)
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Animal</th>
                        <th>Tutor / Telefone</th>
                        <th>Vacina / Laboratório</th>
                        <th>Última Aplicação</th>
                        <th>Próxima Prevista</th>
                        <th>Situação</th>
                        <th>Contato com Tutor</th>
                        <th style="width: 215px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($todayList as $vac)
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
                                    <span class="badge badge-success ml-1" title="Autorizou WhatsApp">Opt-in</span>
                                @elseif($tutor->whatsapp_opt_in === false)
                                    <span class="badge badge-danger ml-1" title="Recusou WhatsApp">Opt-out</span>
                                @else
                                    <span class="badge badge-light border ml-1" title="Sem autorização confirmada">Não conf.</span>
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
                        <td>
                            <span class="badge badge-{{ $vac->classification_badge }}">{{ $vac->classification_label }}</span>
                        </td>
                        <td>
                            @if($lastRem)
                                <span class="badge badge-{{ $lastRem->status_badge }}">{{ $lastRem->status_label }}</span>
                                <br><small class="text-muted">{{ ($lastRem->sent_at ?? $lastRem->created_at)?->format('d/m H:i') }}</small>
                            @elseif($vac->last_contact_at)
                                <span class="badge badge-info">Contatado</span>
                                <br><small class="text-muted">{{ $vac->last_contact_at->format('d/m H:i') }}</small>
                            @else
                                <span class="badge badge-light border">Sem contato</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if($vac->pet)
                                <a href="{{ route('vaccinations.index', ['pet_id' => $vac->pet_id]) }}" class="btn btn-xs btn-secondary" title="Histórico de Vacinação"><i class="fas fa-history"></i></a>
                                <a href="{{ route('pets.show', $vac->pet) }}" class="btn btn-xs btn-info" title="Abrir Cadastro do Animal"><i class="fas fa-paw"></i></a>
                            @endif
                            @if($tutor)
                                <a href="{{ route('tutors.show', $tutor) }}" class="btn btn-xs btn-outline-info" title="Abrir Cadastro do Tutor"><i class="fas fa-user"></i></a>
                                <button type="button" onclick="openWhatsAppPreviewModal({{ $vac->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar Mensagem pelo WhatsApp"><i class="fab fa-whatsapp"></i></button>
                            @endif
                            <button type="button" onclick="openRegisterContactModal({{ $vac->id }}, '{{ addslashes(($vac->pet->name ?? '') . ' - ' . $vac->vaccine) }}', '{{ addslashes((string) ($vac->last_contact_notes ?? '')) }}')" class="btn btn-xs btn-outline-secondary" title="Registrar Contato Realizado"><i class="fas fa-phone-alt"></i></button>
                            <a href="{{ route('vaccinations.create', ['pet_id' => $vac->pet_id, 'complete_vaccination_id' => $vac->id]) }}" class="btn btn-xs btn-primary" title="Registrar Vacinação Aplicada"><i class="fas fa-check"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted text-center mb-0 py-3">Nenhuma vacinação prevista para hoje ({{ now()->format('d/m/Y') }}).</p>
        @endif
    </div>
</div>
@endcan
