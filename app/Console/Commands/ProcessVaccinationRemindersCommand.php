<?php

namespace App\Console\Commands;

use App\Services\Vaccination\VaccinationNotificationCenterService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessVaccinationRemindersCommand extends Command
{
    protected $signature = 'vaccines:process-reminders
                            {--dry-run : Apenas simula a identificacao sem gravar ou enviar mensagens}
                            {--only-prepare : Apenas identifica pendencias e prepara os lembretes}
                            {--only-send : Apenas dispara os lembretes pendentes ja preparados}
                            {--limit=100 : Limite maximo de mensagens por lote de envio}';

    protected $description = 'Rotina diaria automatizada de acompanhamento de vacinacoes e envio de lembretes via WhatsApp';

    public function handle(VaccinationNotificationCenterService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyPrepare = (bool) $this->option('only-prepare');
        $onlySend = (bool) $this->option('only-send');
        $limit = max(1, (int) $this->option('limit'));

        $lock = Cache::lock('vaccines_process_reminders_lock', 600);
        if (!$lock->get()) {
            $this->warn('Rotina ja esta em execucao em outro processo. Execucao simultanea bloqueada com seguranca.');
            return self::SUCCESS;
        }

        try {
            $today = Carbon::now('America/Sao_Paulo')->startOfDay();
            $this->info('=== CENTRAL AUTOMATIZADA DE LEMBRETES DE VACINACAO ===');
            $this->line('Data de referencia (America/Sao_Paulo): ' . $today->format('d/m/Y'));

            $prep = null;
            if (!$onlySend) {
                $prep = $service->prepareReminders($dryRun, $today);
                $this->table(
                    ['Etapa de Identificacao / Preparacao', 'Qtd'],
                    [
                        ['Verificadas: 7 dias antes', $prep['checked_7_days']],
                        ['Verificadas: vencem hoje', $prep['checked_due_today']],
                        ['Verificadas: em atraso (janela configurada)', $prep['checked_overdue']],
                        ['Novos lembretes preparados', $prep['prepared']],
                        ['Ignorados: ja preparados/enviados (duplicidade evitada)', $prep['skipped_duplicate']],
                        ['Ignorados: tutor sem autorizacao WhatsApp (LGPD)', $prep['skipped_no_opt_in']],
                        ['Ignorados: tutor sem telefone valido', $prep['skipped_invalid_phone']],
                        ['Ignorados: pet sem tutor', $prep['skipped_no_tutor']],
                        ['Ignorados: dados obrigatorios ausentes', $prep['skipped_invalid_data']],
                        ['Ignorados: limite de tentativas de atraso atingido', $prep['skipped_max_attempts']],
                        ['Ignorados: aguardando intervalo entre tentativas', $prep['skipped_interval_wait']],
                    ]
                );
            }

            $disp = null;
            if (!$dryRun && !$onlyPrepare) {
                $disp = $service->dispatchPendingReminders($limit, true);
                if ($disp['auto_send_disabled']) {
                    $this->warn('Envio automatico de WhatsApp esta DESATIVADO nas configuracoes. Lembretes mantidos como preparados/aguardando.');
                } else {
                    $this->info("Lote processado: {$disp['processed']} | Enviadas a API: {$disp['sent_to_api']} | Falhas: {$disp['failed']} | Canceladas: {$disp['cancelled']}");
                }
            }

            Log::info('vaccines:process-reminders executed', [
                'dry_run' => $dryRun,
                'prepare' => $prep,
                'dispatch' => $disp,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('vaccines:process-reminders error', ['error' => $e->getMessage()]);
            $this->error('Erro durante o processamento: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
