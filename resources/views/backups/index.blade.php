@extends('layouts.adminlte', ['title' => 'Backup e Restauração'])

@section('content')
<div class="row">
    <!-- Indicadores Rápidos -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Base de Dados</span>
                <span class="info-box-number">{{ $dbInfo['driver'] }} ({{ $dbInfo['size'] }})</span>
                <small class="text-muted">{{ $dbInfo['database'] }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success"><i class="fas fa-archive"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Backups Salvos</span>
                <span class="info-box-number">{{ $dbInfo['total_backups'] }} arquivos</span>
                <small class="text-muted">Total: {{ $dbInfo['total_backup_size'] }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Último Backup</span>
                <span class="info-box-number" style="font-size: 0.95rem;">{{ $dbInfo['last_backup'] }}</span>
                <small class="text-muted">Local: storage/app/backups</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon {{ $settings['enabled'] ? 'bg-primary' : 'bg-secondary' }}">
                <i class="fas {{ $settings['enabled'] ? 'fa-calendar-check' : 'fa-calendar-times' }}"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Rotina Automática</span>
                <span class="info-box-number">
                    {{ $settings['enabled'] ? 'Ativa' : 'Desativada' }}
                </span>
                <small class="text-muted">
                    @if($settings['enabled'])
                        {{ match($settings['frequency']) {
                            'every_six_hours' => 'A cada 6 horas',
                            'every_twelve_hours' => 'A cada 12 horas',
                            'weekly' => 'Semanal às ' . $settings['time'],
                            'monthly' => 'Mensal (dia 1) às ' . $settings['time'],
                            default => 'Diário às ' . $settings['time']
                        } }}
                    @else
                        Sem agendamento ativo
                    @endif
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Card de Ações Rápidas & Configuração de Rotina -->
    <div class="col-lg-4 col-md-5">
        <!-- Execução Manual -->
        <div class="card card-primary card-outline shadow-sm mb-3">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-play-circle mr-1 text-primary"></i> Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Gere um backup completo da base de dados neste momento com apenas um clique.</p>
                <form action="{{ route('backups.create') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" name="compress" value="1" class="custom-control-input" id="manualCompress" checked>
                        <label class="custom-control-label small" for="manualCompress">Compactar com Gzip (.gz)</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-cloud-download-alt mr-1"></i> Fazer Backup Agora
                    </button>
                </form>

                <hr>

                <button type="button" class="btn btn-outline-warning btn-block mb-2" data-toggle="modal" data-target="#uploadRestoreModal">
                    <i class="fas fa-upload mr-1"></i> Restaurar por Arquivo (Upload)
                </button>

                <form action="{{ route('backups.cleanup') }}" method="POST" onsubmit="return confirm('Deseja remover backups mais antigos que a política de retenção configurada?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-block btn-sm">
                        <i class="fas fa-broom mr-1"></i> Limpar Backups Antigos
                    </button>
                </form>
            </div>
        </div>

        <!-- Configuração de Rotina e Destinos -->
        <div class="card card-info card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-cogs mr-1 text-info"></i> Rotina e Destinos</h3>
            </div>
            <form action="{{ route('backups.settings') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group mb-3">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="enabled" value="1" class="custom-control-input" id="backupEnabled" {{ $settings['enabled'] ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="backupEnabled">Backup Automático Ativo</label>
                        </div>
                        <small class="form-text text-muted">Quando ativo, o sistema gerará backups periódicos em segundo plano.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Frequência do Backup *</label>
                        <select name="frequency" class="form-control" required>
                            <option value="daily" {{ $settings['frequency'] === 'daily' ? 'selected' : '' }}>Diário (todos os dias)</option>
                            <option value="every_twelve_hours" {{ $settings['frequency'] === 'every_twelve_hours' ? 'selected' : '' }}>A cada 12 horas</option>
                            <option value="every_six_hours" {{ $settings['frequency'] === 'every_six_hours' ? 'selected' : '' }}>A cada 6 horas</option>
                            <option value="weekly" {{ $settings['frequency'] === 'weekly' ? 'selected' : '' }}>Semanal (aos Domingos)</option>
                            <option value="monthly" {{ $settings['frequency'] === 'monthly' ? 'selected' : '' }}>Mensal (1º dia de cada mês)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Horário de Execução *</label>
                                <input type="time" name="time" class="form-control" value="{{ $settings['time'] }}" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Retenção (Dias) *</label>
                                <input type="number" name="keep_days" class="form-control" value="{{ $settings['keep_days'] }}" min="1" max="365" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="compress" value="1" class="custom-control-input" id="autoCompress" {{ $settings['compress'] ? 'checked' : '' }}>
                            <label class="custom-control-label small" for="autoCompress">Compactação automática Gzip (.gz)</label>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Destino Adicional / Externo (Opcional)</label>
                        <div class="input-group">
                            <input type="text"
                                   id="customPathInput"
                                   name="custom_path"
                                   class="form-control"
                                   placeholder="Ex: D:\Backups_Clinica ou E:\Backups"
                                   value="{{ $settings['custom_path'] }}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="btnTestDestination" title="Testar permissão de escrita">
                                    <i class="fas fa-check-circle"></i> Testar
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Pasta local, HD externo ou pendrive onde uma cópia idêntica do backup será gravada automaticamente.
                        </small>
                        <div id="destinationFeedback" class="small mt-1" style="display: none;"></div>
                    </div>
                </div>
                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="fas fa-save mr-1"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Backups Existentes -->
    <div class="col-lg-8 col-md-7">
        <div class="card card-secondary card-outline shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-history mr-1"></i> Histórico de Backups Disponíveis ({{ $files->count() }})
                </h3>
            </div>
            <div class="card-body p-0">
                @if($files->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Arquivo de Backup</th>
                                <th>Tipo</th>
                                <th>Tamanho</th>
                                <th>Data e Hora</th>
                                <th style="width: 170px;" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $backup)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark text-break">
                                        <i class="fas fa-file-archive text-warning mr-1"></i>
                                        {{ $backup['name'] }}
                                    </div>
                                    @if($backup['is_compressed'])
                                        <span class="badge badge-success" style="font-size: 0.7rem;">Compactado GZ</span>
                                    @endif
                                </td>
                                <td>
                                    @if($backup['type'] === 'pre_restore')
                                        <span class="badge badge-warning"><i class="fas fa-shield-alt mr-1"></i> Pré-Restauração</span>
                                    @elseif($backup['type'] === 'automatic')
                                        <span class="badge badge-info"><i class="fas fa-robot mr-1"></i> Automático</span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-user mr-1"></i> Manual</span>
                                    @endif
                                </td>
                                <td>{{ $backup['formatted_size'] }}</td>
                                <td>{{ $backup['formatted_date'] }}</td>
                                <td class="text-center">
                                    <!-- Download -->
                                    <a href="{{ route('backups.download', $backup['name']) }}"
                                       class="btn btn-action btn-success mr-1"
                                       title="Baixar arquivo para seu computador">
                                        <i class="fas fa-download"></i>
                                    </a>

                                    <!-- Restaurar -->
                                    <button type="button"
                                            class="btn btn-action btn-warning mr-1 btn-restore"
                                            data-filename="{{ $backup['name'] }}"
                                            data-date="{{ $backup['formatted_date'] }}"
                                            data-size="{{ $backup['formatted_size'] }}"
                                            title="Restaurar a base de dados a partir deste backup">
                                        <i class="fas fa-undo-alt"></i>
                                    </button>

                                    <!-- Excluir -->
                                    <form action="{{ route('backups.destroy', $backup['name']) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza de que deseja excluir permanentemente o arquivo {{ $backup['name'] }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-action btn-danger" title="Excluir backup">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                    <p class="mb-1 font-weight-bold">Nenhum backup encontrado no armazenamento local.</p>
                    <p class="small">Clique em <strong>"Fazer Backup Agora"</strong> para gerar a primeira cópia de segurança do banco de dados.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Seguro de Restauração -->
<div class="modal fade" id="restoreModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Confirmar Restauração de Base de Dados</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('backups.restore') }}" method="POST" id="restoreForm">
                @csrf
                <input type="hidden" name="filename" id="restoreFilenameInput">
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-radiation-alt mr-1"></i>
                        <strong>AÇÃO CRÍTICA:</strong> A restauração irá substituir completamente a base de dados atual pelo conteúdo do backup selecionado!
                    </div>

                    <div class="bg-light p-3 rounded border mb-3">
                        <div><strong>Arquivo selecionado:</strong> <span id="modalRestoreFilename" class="text-danger font-weight-bold"></span></div>
                        <div><strong>Data do backup:</strong> <span id="modalRestoreDate"></span></div>
                        <div><strong>Tamanho:</strong> <span id="modalRestoreSize"></span></div>
                    </div>

                    <div class="callout callout-info small mb-3">
                        <i class="fas fa-shield-alt text-info mr-1"></i>
                        <strong>Snapshot de segurança automático:</strong> O sistema criará automaticamente uma cópia de segurança preventiva da base atual antes de iniciar a restauração.
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small text-dark">
                            Para confirmar, digite exatamente <span class="badge badge-danger">RESTAURAR</span> no campo abaixo:
                        </label>
                        <input type="text"
                               name="confirmation"
                               id="restoreConfirmInput"
                               class="form-control text-center font-weight-bold text-danger"
                               placeholder="Digite RESTAURAR"
                               autocomplete="off"
                               required>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnConfirmRestore" class="btn btn-danger" disabled>
                        <i class="fas fa-undo-alt mr-1"></i> Restaurar Agora
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Restauração por Upload de Arquivo -->
<div class="modal fade" id="uploadRestoreModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-warning">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-upload mr-1"></i> Restaurar Base de Dados por Arquivo</h5>
                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('backups.restore') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Envie um arquivo de backup (.sql, .sqlite ou compactado .gz) para substituir a base de dados do sistema.
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Selecione o arquivo de backup (.sql, .sqlite, .gz) *</label>
                        <input type="file" name="backup_file" class="form-control-file" accept=".sql,.sqlite,.gz" required>
                    </div>

                    <div class="callout callout-info small mb-3">
                        <i class="fas fa-shield-alt text-info mr-1"></i>
                        Um snapshot de segurança da base atual será gerado automaticamente antes da substituição.
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small text-dark">
                            Para confirmar, digite exatamente <span class="badge badge-danger">RESTAURAR</span> no campo abaixo:
                        </label>
                        <input type="text"
                               name="confirmation"
                               id="uploadConfirmInput"
                               class="form-control text-center font-weight-bold text-danger"
                               placeholder="Digite RESTAURAR"
                               autocomplete="off"
                               required>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnConfirmUploadRestore" class="btn btn-danger" disabled>
                        <i class="fas fa-upload mr-1"></i> Enviar e Restaurar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Modal de Restauração a partir da lista
    var restoreModal = $('#restoreModal');
    $('.btn-restore').on('click', function () {
        var filename = $(this).data('filename');
        var date = $(this).data('date');
        var size = $(this).data('size');

        $('#restoreFilenameInput').val(filename);
        $('#modalRestoreFilename').text(filename);
        $('#modalRestoreDate').text(date);
        $('#modalRestoreSize').text(size);
        $('#restoreConfirmInput').val('');
        $('#btnConfirmRestore').prop('disabled', true);

        restoreModal.modal('show');
    });

    $('#restoreConfirmInput').on('input', function () {
        var val = $(this).val().trim();
        $('#btnConfirmRestore').prop('disabled', val !== 'RESTAURAR');
    });

    // Modal de Restauração por Upload
    $('#uploadConfirmInput').on('input', function () {
        var val = $(this).val().trim();
        $('#btnConfirmUploadRestore').prop('disabled', val !== 'RESTAURAR');
    });

    // Testar Destino Adicional em Tempo Real
    $('#btnTestDestination').on('click', function () {
        var path = $('#customPathInput').val().trim();
        var feedback = $('#destinationFeedback');
        var btn = $(this);

        if (!path) {
            feedback.removeClass('text-success').addClass('text-danger').text('Informe um caminho para testar.').show();
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testando...');
        feedback.hide();

        fetch("{{ route('backups.test-destination') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ custom_path: path })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Testar');
            feedback.removeClass('text-success text-danger');
            if (data.success) {
                feedback.addClass('text-success').html('<i class="fas fa-check mr-1"></i> ' + data.message).show();
            } else {
                feedback.addClass('text-danger').html('<i class="fas fa-times-circle mr-1"></i> ' + data.message).show();
            }
        })
        .catch(function (err) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Testar');
            feedback.removeClass('text-success').addClass('text-danger').text('Erro ao testar destino.').show();
        });
    });
});
</script>
@endpush
