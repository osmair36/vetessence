{{-- Modal: Pre-visualizar e Enviar WhatsApp Manual --}}
<div class="modal fade" id="waPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="waPreviewForm" method="POST" action="">
                @csrf
                <input type="hidden" name="reminder_type" id="waReminderType" value="manual">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fab fa-whatsapp mr-2"></i>Enviar Lembrete de Vacinação via WhatsApp</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="waPreviewLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                        <p class="mt-2 text-muted">Preparando mensagem personalizada...</p>
                    </div>
                    <div id="waPreviewError" class="alert alert-danger d-none"></div>
                    <div id="waPreviewContent" class="d-none">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <small class="text-muted text-uppercase">Animal / Vacina</small>
                                <p class="mb-1"><strong id="waPetName"></strong> — <span id="waVaccineName"></span> <span id="waLabBadge" class="badge badge-light border"></span></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted text-uppercase">Data Prevista</small>
                                <p class="mb-1"><strong id="waNextDate"></strong></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Tutor Destinatário</label>
                            <select name="tutor_id" id="waTutorSelect" class="form-control" required></select>
                            <small id="waPhoneInfo" class="form-text"></small>
                        </div>

                        <div id="waOptInWarning" class="alert alert-warning py-2 d-none">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Atenção (LGPD):</strong> <span id="waOptInWarningText"></span>
                        </div>

                        <ul class="nav nav-pills mb-3" id="waModalTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-1 px-3" id="wa-tab-visual" data-toggle="pill" href="#wa-pane-visual" role="tab">
                                    <i class="fab fa-whatsapp text-success mr-1"></i> Visual no Celular (WhatsApp)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3" id="wa-tab-edit" data-toggle="pill" href="#wa-pane-edit" role="tab">
                                    <i class="fas fa-edit mr-1"></i> Editar Texto
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Visual no Celular -->
                            <div class="tab-pane fade show active" id="wa-pane-visual" role="tabpanel">
                                <div style="background-color: #efeae2; border: 1px solid #d1d7db; border-radius: 12px; padding: 12px; max-width: 520px; margin: 0 auto; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                    <!-- Topo com Logo da Clínica -->
                                    <div style="background-color: #008069; color: white; border-radius: 8px 8px 0 0; padding: 8px 12px; display: flex; align-items: center; gap: 10px; margin: -12px -12px 10px -12px;">
                                        <img src="{{ asset('img/logo-amigobicho.png') }}" style="width: 38px; height: 38px; border-radius: 50%; background: #ffffff; padding: 2px; object-fit: contain;">
                                        <div>
                                            <div style="font-weight: bold; font-size: 13px; line-height: 1.2;">Clínica Veterinária Amigo Bicho <i class="fas fa-check-circle text-warning ml-1" style="font-size: 11px;"></i></div>
                                            <div style="font-size: 10px; opacity: 0.9;">Conta comercial oficial • Online</div>
                                        </div>
                                    </div>
                                    <!-- Balão de Mensagem -->
                                    <div style="background-color: #ffffff; border-radius: 0 8px 8px 8px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.12); font-size: 13.5px; line-height: 1.5; color: #111b21;">
                                        <div id="waVisualBalloon" style="white-space: pre-line;"></div>
                                        <div style="text-align: right; font-size: 10px; color: #667781; margin-top: 6px;">09:00 <i class="fas fa-check-double text-primary ml-1" style="font-size: 9px;"></i></div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Para alterar qualquer palavra da mensagem, clique na aba <strong>Editar Texto</strong> acima.</small>
                                </div>
                            </div>

                            <!-- Edição de Texto -->
                            <div class="tab-pane fade" id="wa-pane-edit" role="tabpanel">
                                <div class="form-group mb-0">
                                    <label>Texto da Mensagem <small class="text-muted">(ajuste livremente antes de enviar)</small></label>
                                    <textarea name="message_body" id="waMessageBody" rows="9" class="form-control" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="waConfirmSendBtn" class="btn btn-success">
                        <i class="fab fa-whatsapp mr-1"></i> Confirmar e Enviar WhatsApp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Registrar Contato Realizado com o Tutor --}}
<div class="modal fade" id="registerContactModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="registerContactForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-phone-alt mr-2"></i>Registrar Contato com o Tutor</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="registerContactSubtitle"></p>
                    <div class="form-group mb-0">
                        <label>Observações do contato *</label>
                        <textarea name="contact_notes" id="registerContactNotes" rows="4" class="form-control" required placeholder="Ex: Ligação realizada, tutor confirmou retorno na sexta-feira..."></textarea>
                        <small class="form-text text-muted">Registrar o contato não conclui a vacinação — apenas atualiza o acompanhamento.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Salvar Contato</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    if (window.__waCenterModalsBound) return;
    window.__waCenterModalsBound = true;

    const previewBaseUrl = "{{ url('/central-vacinas/preview') }}";
    const sendBaseUrl = "{{ url('/central-vacinas/send-manual') }}";
    const contactBaseUrl = "{{ url('/central-vacinas/register-contact') }}";
    let currentVaccinationId = null;

    window.openWhatsAppPreviewModal = function(vaccinationId, tutorId) {
        currentVaccinationId = vaccinationId;
        const modal = $('#waPreviewModal');
        $('#waPreviewLoading').removeClass('d-none');
        $('#waPreviewError').addClass('d-none').text('');
        $('#waPreviewContent').addClass('d-none');
        $('#waConfirmSendBtn').prop('disabled', true);
        $('#waPreviewForm').attr('action', sendBaseUrl + '/' + vaccinationId);
        modal.modal('show');

        let url = previewBaseUrl + '/' + vaccinationId;
        if (tutorId) url += '?tutor_id=' + encodeURIComponent(tutorId);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                $('#waPreviewLoading').addClass('d-none');
                if (!data.ok && !data.message) {
                    $('#waPreviewError').removeClass('d-none').text(data.error || 'Não foi possível preparar a mensagem.');
                    return;
                }

                $('#waPetName').text(data.pet_name || '-');
                $('#waVaccineName').text(data.vaccine || '-');
                if (data.laboratory) {
                    $('#waLabBadge').text(data.laboratory).removeClass('d-none');
                } else {
                    $('#waLabBadge').addClass('d-none');
                }
                $('#waNextDate').text(data.next_date || '-');
                $('#waReminderType').val(data.reminder_type || 'manual');
                $('#waMessageBody').val(data.message || '');
                $('#waVisualBalloon').text(data.message || '');

                const sel = $('#waTutorSelect').empty();
                (data.tutors || []).forEach(t => {
                    const opt = $('<option>').val(t.id).text(t.name + (t.phone ? ' (' + t.phone + ')' : ' (sem telefone)'));
                    if (String(t.id) === String(data.tutor_id)) opt.prop('selected', true);
                    sel.append(opt);
                });

                if (data.has_valid_phone) {
                    $('#waPhoneInfo').removeClass('text-danger').addClass('text-success')
                        .html('<i class="fas fa-check-circle mr-1"></i>Telefone válido para WhatsApp: +' + data.phone_normalized);
                    $('#waConfirmSendBtn').prop('disabled', false);
                } else {
                    $('#waPhoneInfo').removeClass('text-success').addClass('text-danger')
                        .html('<i class="fas fa-times-circle mr-1"></i>Telefone inválido ou ausente (' + (data.phone_raw || 'vazio') + '). Atualize o cadastro do tutor antes de enviar.');
                    $('#waConfirmSendBtn').prop('disabled', true);
                }

                if (data.whatsapp_opt_in === true) {
                    $('#waOptInWarning').addClass('d-none');
                } else if (data.whatsapp_opt_in === false) {
                    $('#waOptInWarningText').text('Este tutor marcou que NÃO deseja receber mensagens pelo WhatsApp. Confirme apenas se houver solicitação expressa.');
                    $('#waOptInWarning').removeClass('d-none');
                } else {
                    $('#waOptInWarningText').text('Este tutor ainda não possui autorização formal registrada para envios automáticos. Você pode prosseguir com o envio manual ou registrar a autorização no cadastro.');
                    $('#waOptInWarning').removeClass('d-none');
                }

                $('#waPreviewContent').removeClass('d-none');
            })
            .catch(() => {
                $('#waPreviewLoading').addClass('d-none');
                $('#waPreviewError').removeClass('d-none').text('Erro de comunicação ao carregar a pré-visualização.');
            });
    };

    $(document).on('change', '#waTutorSelect', function() {
        if (currentVaccinationId) {
            window.openWhatsAppPreviewModal(currentVaccinationId, $(this).val());
        }
    });

    $(document).on('input', '#waMessageBody', function() {
        $('#waVisualBalloon').text($(this).val());
    });

    window.openRegisterContactModal = function(vaccinationId, label, currentNotes) {
        $('#registerContactForm').attr('action', contactBaseUrl + '/' + vaccinationId);
        $('#registerContactSubtitle').text(label || '');
        $('#registerContactNotes').val(currentNotes || '');
        $('#registerContactModal').modal('show');
    };
})();
</script>
@endpush
