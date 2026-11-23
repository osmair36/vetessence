@can('lembrete-vacinas')
@php
    $vs = $vaccineStats ?? [];
    $todayList = $todayVaccinations ?? collect();
    $bdayList = $todayBirthdays ?? collect();
    $groomList = $todayGroomings ?? collect();
@endphp
<div class="card card-outline card-success mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title mb-0">
            <i class="fab fa-whatsapp text-success mr-2"></i>
            <strong>Acompanhamento de Vacinações, Aniversariantes, Banho e Tosa e Lembretes WhatsApp</strong>
            @if(!empty($vs['auto_send_enabled']))
                <span class="badge badge-success ml-2"><i class="fab fa-whatsapp mr-1"></i>Envio Automático Ativo</span>
            @else
                <span class="badge badge-secondary ml-2"><i class="fas fa-pause-circle mr-1"></i>Envio Automático Desativado</span>
            @endif
        </h3>
        <div class="card-tools">
            <a href="{{ route('vaccination-center.index') }}" class="btn btn-sm btn-success">
                <i class="fab fa-whatsapp mr-1"></i> Abrir Central WhatsApp
            </a>
        </div>
    </div>
    <div class="card-body">
        {{-- Linha 1: Vacinações (4 Indicadores) --}}
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
                            <span class="info-box-text">Vacinas Próx. 7 Dias</span>
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
                            <span class="info-box-text">Vacinas Próx. 30 Dias</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['next_30_days'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Linha 2: Aniversariantes (Pets) e Agendamentos de Banho e Tosa --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'today']) }}" class="text-decoration-none">
                    <div class="info-box mb-0 shadow-sm" style="background-color: #e83e8c; color: #fff;">
                        <span class="info-box-icon"><i class="fas fa-birthday-cake"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Aniversariantes Hoje</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['birthdays_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'month']) }}" class="text-decoration-none">
                    <div class="info-box bg-purple mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-gift"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Aniversariantes no Mês</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['birthdays_month'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'today']) }}" class="text-decoration-none">
                    <div class="info-box bg-teal mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-bath"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Banho e Tosa Hoje</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['grooming_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6 col-6 mb-3">
                <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'next_7_days']) }}" class="text-decoration-none">
                    <div class="info-box bg-indigo mb-0 shadow-sm">
                        <span class="info-box-icon"><i class="fas fa-cut"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Banho e Tosa (Próx. 7d)</span>
                            <span class="info-box-number h4 mb-0">{{ $vs['grooming_next_7_days'] ?? 0 }}</span>
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

        {{-- Listas Rapidas: Aniversariantes de Hoje e Banho e Tosa Hoje --}}
        <div class="row mt-4">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-birthday-cake mr-1" style="color: #e83e8c;"></i> Aniversariantes do Dia ({{ now()->format('d/m') }})</h6>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'aniversariantes', 'bday_bucket' => 'today']) }}" class="btn btn-xs btn-outline-primary">Ver todos</a>
                </div>
                @if($bdayList->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Pet</th>
                                <th>Tutor / Telefone</th>
                                <th style="width: 85px;">WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bdayList as $pet)
                            @php $tutor = $pet->tutors->first(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('pets.show', $pet) }}"><strong>{{ $pet->name }}</strong></a>
                                    <br><small class="text-muted">{{ $pet->birth_date?->format('d/m/Y') }} ({{ (int) $pet->birth_date?->diffInYears(now()) }} ano(s))</small>
                                </td>
                                <td>
                                    @if($tutor)
                                        <a href="{{ route('tutors.show', $tutor) }}">{{ $tutor->name }}</a><br>
                                        <small class="text-muted">{{ $tutor->phone ?: 'Sem telefone' }}</small>
                                    @else
                                        <span class="text-danger small">Sem tutor</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($tutor)
                                        <button type="button" onclick="openBirthdayWhatsAppModal({{ $pet->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar Parabéns pelo WhatsApp">
                                            <i class="fab fa-whatsapp"></i> Enviar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted small text-center border rounded py-3 mb-0">Nenhum pet aniversariante hoje.</p>
                @endif
            </div>

            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 font-weight-bold"><i class="fas fa-bath text-teal mr-1"></i> Agendamentos de Banho e Tosa Hoje ({{ now()->format('d/m/Y') }})</h6>
                    <a href="{{ route('vaccination-center.index', ['tab' => 'banho_tosa', 'groom_bucket' => 'today']) }}" class="btn btn-xs btn-outline-primary">Ver todos</a>
                </div>
                @if($groomList->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Horário / Pet</th>
                                <th>Tutor / Serviço</th>
                                <th style="width: 85px;">WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groomList as $appt)
                            @php
                                $tutor = $appt->pet?->tutors?->first();
                                $svcNames = $appt->services->map(fn($s) => $s->service?->name)->filter()->implode(', ') ?: ($appt->reason ?: 'Banho e Tosa');
                                $hora = $appt->time ? (is_string($appt->time) ? substr($appt->time, 0, 5) : $appt->time->format('H:i')) : '--:--';
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge badge-info">{{ $hora }}</span>
                                    @if($appt->pet)
                                        <a href="{{ route('pets.show', $appt->pet) }}"><strong>{{ $appt->pet->name }}</strong></a>
                                    @endif
                                </td>
                                <td>
                                    @if($tutor)
                                        <a href="{{ route('tutors.show', $tutor) }}">{{ $tutor->name }}</a>
                                    @else
                                        <span class="text-danger small">Sem tutor</span>
                                    @endif
                                    <br><small class="text-muted">{{ $svcNames }}</small>
                                </td>
                                <td class="text-center align-middle">
                                    @if($tutor)
                                        <button type="button" onclick="openGroomingWhatsAppModal({{ $appt->id }}, {{ $tutor->id }})" class="btn btn-xs btn-success" title="Enviar Lembrete de Banho e Tosa">
                                            <i class="fab fa-whatsapp"></i> Enviar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted small text-center border rounded py-3 mb-0">Nenhum agendamento de Banho e Tosa para hoje.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endcan
