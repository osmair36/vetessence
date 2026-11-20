<?php

namespace App\Services\Vaccination;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Tutor;
use App\Models\Vaccination;
use App\Models\VaccinationReminder;
use App\Services\Notification\NotificationChannel;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VaccinationNotificationCenterService
{
    public const CACHE_STATS_KEY = 'vaccination_center_dashboard_stats';

    /** @var array<string, string> */
    protected array $protocolFamilies = [
        '001' => 'V10', '002' => 'V10', '003' => 'V10',
        '005' => 'GRIPE', '006' => 'GRIPE', '055' => 'GRIPE', '35' => 'GRIPE',
        '007' => 'GIARDIA', '008' => 'GIARDIA',
        '009' => 'FELOCEL_V4', '010' => 'FELOCEL_V4',
        '035' => 'QUINTUPLA_FELINA', '037' => 'QUINTUPLA_FELINA',
        '013' => 'FELOCELL_FELV', '014' => 'FELOCELL_FELV',
        '011' => 'VERMIFUGO', '032' => 'VERMIFUGO', '33' => 'VERMIFUGO', '111' => 'VERMIFUGO',
        '019' => 'ADVOCATE', '50' => 'ADVOCATE',
        '30' => 'REVOLUTION', '033' => 'REVOLUTION',
    ];

    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function getSettings(): array
    {
        return [
            'auto_send_enabled' => Setting::get('vaccine_reminders.auto_send_enabled', '0') === '1',
            'remind_7_days_enabled' => Setting::get('vaccine_reminders.remind_7_days_enabled', '1') === '1',
            'remind_due_today_enabled' => Setting::get('vaccine_reminders.remind_due_today_enabled', '1') === '1',
            'remind_overdue_enabled' => Setting::get('vaccine_reminders.remind_overdue_enabled', '1') === '1',
            'overdue_interval_days' => max(1, (int) Setting::get('vaccine_reminders.overdue_interval_days', '7')),
            'max_overdue_attempts' => max(1, (int) Setting::get('vaccine_reminders.max_overdue_attempts', '3')),
            'overdue_max_age_days' => max(7, (int) Setting::get('vaccine_reminders.overdue_max_age_days', '180')),
            'daily_run_time' => (string) Setting::get('vaccine_reminders.daily_run_time', '09:00'),
            'template_7_days' => (string) Setting::get('vaccine_reminders.template_7_days', $this->defaultTemplate(VaccinationReminder::TYPE_BEFORE_7_DAYS)),
            'template_due_today' => (string) Setting::get('vaccine_reminders.template_due_today', $this->defaultTemplate(VaccinationReminder::TYPE_DUE_TODAY)),
            'template_overdue' => (string) Setting::get('vaccine_reminders.template_overdue', $this->defaultTemplate(VaccinationReminder::TYPE_OVERDUE)),
        ];
    }

    public function saveSettings(array $data): void
    {
        $boolKeys = [
            'auto_send_enabled',
            'remind_7_days_enabled',
            'remind_due_today_enabled',
            'remind_overdue_enabled',
        ];

        foreach ($boolKeys as $key) {
            Setting::set("vaccine_reminders.{$key}", !empty($data[$key]) ? '1' : '0');
        }

        if (isset($data['overdue_interval_days'])) {
            Setting::set('vaccine_reminders.overdue_interval_days', (string) max(1, (int) $data['overdue_interval_days']));
        }
        if (isset($data['max_overdue_attempts'])) {
            Setting::set('vaccine_reminders.max_overdue_attempts', (string) max(1, (int) $data['max_overdue_attempts']));
        }
        if (isset($data['overdue_max_age_days'])) {
            Setting::set('vaccine_reminders.overdue_max_age_days', (string) max(7, (int) $data['overdue_max_age_days']));
        }
        if (!empty($data['daily_run_time'])) {
            Setting::set('vaccine_reminders.daily_run_time', (string) $data['daily_run_time']);
        }
        foreach (['template_7_days', 'template_due_today', 'template_overdue'] as $tplKey) {
            if (array_key_exists($tplKey, $data) && trim((string) $data[$tplKey]) !== '') {
                Setting::set("vaccine_reminders.{$tplKey}", (string) $data[$tplKey]);
            }
        }

        Cache::forget(self::CACHE_STATS_KEY);
    }

    public function defaultTemplate(string $type): string
    {
        return match ($type) {
            VaccinationReminder::TYPE_BEFORE_7_DAYS => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nEstamos passando para lembrar que a vacinação do seu pet {NOME_ANIMAL} está prevista para daqui a 7 dias.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nÉ importante manter o acompanhamento da vacinação do seu companheiro em dia.\n\nEntre em contato conosco para agendar a aplicação!\n\nSerá um prazer receber vocês! ❤️",
            VaccinationReminder::TYPE_DUE_TODAY => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nHoje está prevista a vacinação do seu pet {NOME_ANIMAL}.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nEstamos à disposição para agendar a aplicação e manter a saúde do seu companheiro em dia.\n\nEntre em contato conosco!",
            default => "Olá, {NOME_TUTOR}! Tudo bem? 🐾\n\nIdentificamos que a vacinação do seu pet {NOME_ANIMAL} ainda consta como pendente em nosso sistema.\n\n💉 Vacina: {NOME_VACINA}\n\n📅 Data prevista: {DATA_VACINACAO}\n\nGostaríamos de lembrar que é importante manter o calendário de vacinação atualizado.\n\nEntre em contato conosco para verificar a situação e agendar o atendimento.\n\nAguardamos vocês! ❤️",
        };
    }

    /**
     * Indicadores otimizados do painel de vacinacoes no Dashboard (consultas indexadas <5ms).
     */
    public function getDashboardStats(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_STATS_KEY);
        }

        return Cache::remember(self::CACHE_STATS_KEY, 60, function () {
            $today = Carbon::today()->toDateString();
            $tomorrow = Carbon::tomorrow()->toDateString();
            $in7 = Carbon::today()->addDays(7)->toDateString();
            $in30 = Carbon::today()->addDays(30)->toDateString();

            $vacCounts = DB::table('vaccinations as v')
                ->join('pets as p', 'p.id', '=', 'v.pet_id')
                ->where('p.is_active', true)
                ->where('v.followup_status', Vaccination::FOLLOWUP_PENDING)
                ->whereNotNull('v.next_date')
                ->selectRaw("
                    SUM(CASE WHEN v.next_date = ? THEN 1 ELSE 0 END) as due_today,
                    SUM(CASE WHEN v.next_date < ? THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN v.next_date >= ? AND v.next_date <= ? THEN 1 ELSE 0 END) as next_7_days,
                    SUM(CASE WHEN v.next_date >= ? AND v.next_date <= ? THEN 1 ELSE 0 END) as next_30_days
                ", [$today, $today, $tomorrow, $in7, $tomorrow, $in30])
                ->first();

            $msgCounts = DB::table('vaccination_reminders')
                ->where(function ($q) {
                    $q->where('channel', 'whatsapp')->orWhereNull('channel');
                })
                ->selectRaw("
                    SUM(CASE WHEN status IN ('sent_to_api', 'sent', 'delivered', 'read') THEN 1 ELSE 0 END) as messages_sent,
                    SUM(CASE WHEN status IN ('prepared', 'pending') THEN 1 ELSE 0 END) as messages_pending,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as messages_failed,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as messages_delivered,
                    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as messages_read,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as messages_cancelled
                ")
                ->first();

            return [
                'due_today' => (int) ($vacCounts->due_today ?? 0),
                'overdue' => (int) ($vacCounts->overdue ?? 0),
                'next_7_days' => (int) ($vacCounts->next_7_days ?? 0),
                'next_30_days' => (int) ($vacCounts->next_30_days ?? 0),
                'messages_sent' => (int) ($msgCounts->messages_sent ?? 0),
                'messages_pending' => (int) ($msgCounts->messages_pending ?? 0),
                'messages_failed' => (int) ($msgCounts->messages_failed ?? 0),
                'messages_delivered' => (int) ($msgCounts->messages_delivered ?? 0),
                'messages_read' => (int) ($msgCounts->messages_read ?? 0),
                'messages_cancelled' => (int) ($msgCounts->messages_cancelled ?? 0),
                'last_run_at' => Setting::get('vaccine_reminders.last_run_at', null),
                'auto_send_enabled' => Setting::get('vaccine_reminders.auto_send_enabled', '0') === '1',
                'whatsapp_provider' => notification_config('whatsapp_provider', ''),
            ];
        });
    }

    /**
     * Lista de vacinacoes previstas para hoje (usada no Dashboard).
     */
    public function getTodayVaccinations(int $limit = 15): Collection
    {
        return Vaccination::with([
                'pet.tutors',
                'vet',
                'reminders' => fn ($q) => $q->orderByDesc('id')->limit(3),
            ])
            ->openPending()
            ->whereDate('next_date', Carbon::today())
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Consulta paginada por classificacao para a Central de Lembretes e cliques do Dashboard.
     */
    public function getVaccinationsByBucket(string $bucket, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $in7 = Carbon::today()->addDays(7)->toDateString();
        $in30 = Carbon::today()->addDays(30)->toDateString();

        $query = Vaccination::with([
            'pet.tutors',
            'vet',
            'completedByVaccination',
            'reminders' => fn ($q) => $q->orderByDesc('id'),
        ]);

        switch ($bucket) {
            case 'due_today':
                $query->openPending()->whereDate('next_date', $today)->orderBy('id');
                break;
            case 'overdue':
                $query->openPending()->whereDate('next_date', '<', $today)->orderBy('next_date', 'desc');
                break;
            case 'next_7_days':
                $query->openPending()->whereBetween('next_date', [$tomorrow, $in7])->orderBy('next_date', 'asc');
                break;
            case 'next_30_days':
                $query->openPending()->whereBetween('next_date', [$tomorrow, $in30])->orderBy('next_date', 'asc');
                break;
            case 'completed':
                $query->where('followup_status', Vaccination::FOLLOWUP_COMPLETED)->orderBy('updated_at', 'desc');
                break;
            default:
                $query->openPending()->orderBy('next_date', 'asc');
                break;
        }

        if ($search) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('vaccine', 'like', "%{$term}%")
                  ->orWhere('laboratory', 'like', "%{$term}%")
                  ->orWhereHas('pet', function ($pq) use ($term) {
                      $pq->where('name', 'like', "%{$term}%")
                         ->orWhereHas('tutors', fn ($tq) => $tq->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
                  });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Renderiza o modelo de mensagem validando que nenhum dado obrigatorio esteja ausente.
     *
     * @return array{ok:bool, message:?string, error:?string}
     */
    public function renderMessage(Vaccination $vaccination, Tutor $tutor, string $type, ?string $customTemplate = null): array
    {
        $tutorName = trim((string) ($tutor->getRawOriginal('name') ?: $tutor->name));
        $petName = trim((string) ($vaccination->pet->name ?? ''));
        $vaccineName = trim((string) ($vaccination->vaccine ?? ''));
        $nextDate = $vaccination->next_date;

        if ($tutorName === '' || $tutorName === 'Tutor') {
            return ['ok' => false, 'message' => null, 'error' => 'Nome do tutor ausente ou inválido.'];
        }
        if ($petName === '') {
            return ['ok' => false, 'message' => null, 'error' => 'Nome do animal ausente.'];
        }
        if ($vaccineName === '') {
            return ['ok' => false, 'message' => null, 'error' => 'Nome da vacina ausente.'];
        }
        if (!$nextDate) {
            return ['ok' => false, 'message' => null, 'error' => 'Data prevista da próxima vacinação não informada.'];
        }

        $settings = $this->getSettings();
        $template = $customTemplate;
        if ($template === null || trim($template) === '') {
            $template = match ($type) {
                VaccinationReminder::TYPE_BEFORE_7_DAYS => $settings['template_7_days'],
                VaccinationReminder::TYPE_DUE_TODAY => $settings['template_due_today'],
                VaccinationReminder::TYPE_OVERDUE => $settings['template_overdue'],
                default => $this->resolveTemplateForVaccination($vaccination, $settings),
            };
        }

        $replacements = [
            '{NOME_TUTOR}' => $tutorName,
            '{NOME_ANIMAL}' => $petName,
            '{NOME_VACINA}' => $vaccineName,
            '{DATA_VACINACAO}' => $nextDate->format('d/m/Y'),
            '{LABORATORIO}' => trim((string) ($vaccination->laboratory ?? '')),
            '{DATA_APLICACAO}' => $vaccination->date ? $vaccination->date->format('d/m/Y') : '',
        ];

        $rendered = trim(str_replace(array_keys($replacements), array_values($replacements), $template));

        // Garante que nenhum placeholder obrigatorio sobrou sem substituicao
        if ($rendered === '' || preg_match('/\{(NOME_TUTOR|NOME_ANIMAL|NOME_VACINA|DATA_VACINACAO)\}/', $rendered)) {
            return ['ok' => false, 'message' => null, 'error' => 'Falha ao preencher variáveis obrigatórias da mensagem.'];
        }

        return ['ok' => true, 'message' => $rendered, 'error' => null];
    }

    public function resolveReminderTypeForVaccination(Vaccination $vaccination, ?Carbon $today = null): string
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        if (!$vaccination->next_date) {
            return VaccinationReminder::TYPE_MANUAL;
        }
        $next = $vaccination->next_date->copy()->startOfDay();
        if ($next->equalTo($today)) {
            return VaccinationReminder::TYPE_DUE_TODAY;
        }
        if ($next->lt($today)) {
            return VaccinationReminder::TYPE_OVERDUE;
        }
        return VaccinationReminder::TYPE_BEFORE_7_DAYS;
    }

    protected function resolveTemplateForVaccination(Vaccination $vaccination, array $settings): string
    {
        $type = $this->resolveReminderTypeForVaccination($vaccination);
        return match ($type) {
            VaccinationReminder::TYPE_BEFORE_7_DAYS => $settings['template_7_days'],
            VaccinationReminder::TYPE_DUE_TODAY => $settings['template_due_today'],
            default => $settings['template_overdue'],
        };
    }

    /**
     * FASE A DA ROTINA DIARIA: Identifica pendencias e prepara lembretes com idempotencia estrita.
     */
    public function prepareReminders(bool $dryRun = false, ?Carbon $referenceToday = null): array
    {
        $today = ($referenceToday ?? Carbon::today())->copy()->startOfDay();
        $settings = $this->getSettings();
        $target7Days = $today->copy()->addDays(7)->toDateString();
        $todayStr = $today->toDateString();
        $overdueMinDate = $today->copy()->subDays($settings['overdue_max_age_days'])->toDateString();

        $summary = [
            'reference_date' => $todayStr,
            'dry_run' => $dryRun,
            'checked_7_days' => 0,
            'checked_due_today' => 0,
            'checked_overdue' => 0,
            'prepared' => 0,
            'skipped_duplicate' => 0,
            'skipped_no_tutor' => 0,
            'skipped_invalid_phone' => 0,
            'skipped_no_opt_in' => 0,
            'skipped_invalid_data' => 0,
            'skipped_max_attempts' => 0,
            'skipped_interval_wait' => 0,
        ];

        $initialStatus = $settings['auto_send_enabled']
            ? VaccinationReminder::STATUS_PENDING
            : VaccinationReminder::STATUS_PREPARED;

        // 1. Vacinas que vencem em exatamente 7 dias
        if ($settings['remind_7_days_enabled']) {
            $list7 = Vaccination::with(['pet.tutors'])
                ->openPending()
                ->whereDate('next_date', $target7Days)
                ->get();

            $summary['checked_7_days'] = $list7->count();
            foreach ($list7 as $vac) {
                $this->queueReminderForVaccination($vac, VaccinationReminder::TYPE_BEFORE_7_DAYS, 1, $todayStr, $initialStatus, $dryRun, $summary);
            }
        }

        // 2. Vacinas que vencem HOJE
        if ($settings['remind_due_today_enabled']) {
            $listToday = Vaccination::with(['pet.tutors'])
                ->openPending()
                ->whereDate('next_date', $todayStr)
                ->get();

            $summary['checked_due_today'] = $listToday->count();
            foreach ($listToday as $vac) {
                $this->queueReminderForVaccination($vac, VaccinationReminder::TYPE_DUE_TODAY, 1, $todayStr, $initialStatus, $dryRun, $summary);
            }
        }

        // 3. Vacinas EM ATRASO (dentro da janela configurada e respeitando intervalo + limite de tentativas)
        if ($settings['remind_overdue_enabled']) {
            $listOverdue = Vaccination::with(['pet.tutors'])
                ->openPending()
                ->whereDate('next_date', '<', $todayStr)
                ->whereDate('next_date', '>=', $overdueMinDate)
                ->get();

            $summary['checked_overdue'] = $listOverdue->count();
            $intervalDays = $settings['overdue_interval_days'];
            $maxAttempts = $settings['max_overdue_attempts'];

            foreach ($listOverdue as $vac) {
                $refDate = $vac->next_date->toDateString();
                $daysOverdue = (int) $vac->next_date->copy()->startOfDay()->diffInDays($today, false);

                if ($daysOverdue < $intervalDays) {
                    $summary['skipped_interval_wait']++;
                    continue;
                }

                $tutors = $vac->pet?->tutors ?? collect();
                if ($tutors->isEmpty()) {
                    $summary['skipped_no_tutor']++;
                    continue;
                }

                foreach ($tutors as $tutor) {
                    $priorOverdue = VaccinationReminder::where('vaccination_id', $vac->id)
                        ->where('tutor_id', $tutor->id)
                        ->where('reminder_type', VaccinationReminder::TYPE_OVERDUE)
                        ->whereDate('reference_date', $refDate)
                        ->where('status', '!=', VaccinationReminder::STATUS_CANCELLED)
                        ->orderByDesc('attempt_number')
                        ->get();

                    $attemptCount = $priorOverdue->count();
                    if ($attemptCount >= $maxAttempts) {
                        $summary['skipped_max_attempts']++;
                        continue;
                    }

                    if ($attemptCount > 0) {
                        $lastReminder = $priorOverdue->first();
                        $lastDate = ($lastReminder->sent_at ?? $lastReminder->scheduled_date ?? $lastReminder->created_at)->copy()->startOfDay();
                        if ($lastDate->diffInDays($today, false) < $intervalDays) {
                            $summary['skipped_interval_wait']++;
                            continue;
                        }
                    }

                    $nextAttempt = $attemptCount + 1;
                    $this->queueReminderForSingleTutor($vac, $tutor, VaccinationReminder::TYPE_OVERDUE, $nextAttempt, $todayStr, $initialStatus, $dryRun, $summary);
                }
            }
        }

        if (!$dryRun) {
            Setting::set('vaccine_reminders.last_run_at', now()->format('Y-m-d H:i:s'));
            Cache::forget(self::CACHE_STATS_KEY);
        }

        return $summary;
    }

    protected function queueReminderForVaccination(
        Vaccination $vac,
        string $type,
        int $attempt,
        string $scheduledDate,
        string $initialStatus,
        bool $dryRun,
        array &$summary
    ): void {
        $tutors = $vac->pet?->tutors ?? collect();
        if ($tutors->isEmpty()) {
            $summary['skipped_no_tutor']++;
            return;
        }

        foreach ($tutors as $tutor) {
            $this->queueReminderForSingleTutor($vac, $tutor, $type, $attempt, $scheduledDate, $initialStatus, $dryRun, $summary);
        }
    }

    protected function queueReminderForSingleTutor(
        Vaccination $vac,
        Tutor $tutor,
        string $type,
        int $attempt,
        string $scheduledDate,
        string $initialStatus,
        bool $dryRun,
        array &$summary
    ): void {
        $phone = $tutor->normalizedWhatsAppPhone();
        if (!$phone) {
            $summary['skipped_invalid_phone']++;
            return;
        }

        if (!$tutor->canReceiveAutoWhatsApp()) {
            $summary['skipped_no_opt_in']++;
            return;
        }

        $refDate = $vac->next_date->toDateString();
        $idempotencyKey = $this->buildIdempotencyKey($vac->id, $tutor->id, $type, $refDate, $attempt);

        if (VaccinationReminder::where('idempotency_key', $idempotencyKey)->exists()) {
            $summary['skipped_duplicate']++;
            return;
        }

        $rendered = $this->renderMessage($vac, $tutor, $type);
        if (!$rendered['ok']) {
            $summary['skipped_invalid_data']++;
            return;
        }

        if ($dryRun) {
            $summary['prepared']++;
            return;
        }

        try {
            VaccinationReminder::create([
                'vaccination_id' => $vac->id,
                'pet_id' => $vac->pet_id,
                'tutor_id' => $tutor->id,
                'reminder_type' => $type,
                'reference_date' => $refDate,
                'attempt_number' => $attempt,
                'idempotency_key' => $idempotencyKey,
                'scheduled_date' => $scheduledDate,
                'channel' => 'whatsapp',
                'destination' => $phone,
                'message_body' => $rendered['message'],
                'status' => $initialStatus,
                'branch_id' => $vac->branch_id ?? $vac->pet?->created_at_branch_id,
            ]);
            $summary['prepared']++;
        } catch (QueryException $e) {
            // Restricao UNIQUE protege contra condicoes de corrida
            $summary['skipped_duplicate']++;
        }
    }

    public function buildIdempotencyKey(int $vaccinationId, int $tutorId, string $type, string $referenceDate, int $attempt = 1): string
    {
        $key = "v:{$vaccinationId}|t:{$tutorId}|type:{$type}|ref:{$referenceDate}";
        if ($type === VaccinationReminder::TYPE_OVERDUE) {
            $key .= "|att:{$attempt}";
        }
        return $key;
    }

    /**
     * FASE B DA ROTINA DIARIA: Envia em lote os lembretes pendentes atraves do NotificationService existente.
     */
    public function dispatchPendingReminders(int $limit = 100, bool $respectAutoSendSetting = true): array
    {
        $settings = $this->getSettings();
        $result = [
            'auto_send_disabled' => false,
            'processed' => 0,
            'sent_to_api' => 0,
            'failed' => 0,
            'cancelled' => 0,
        ];

        if ($respectAutoSendSetting && !$settings['auto_send_enabled']) {
            $result['auto_send_disabled'] = true;
            return $result;
        }

        $reminders = VaccinationReminder::with(['vaccination.pet', 'tutor'])
            ->where('status', VaccinationReminder::STATUS_PENDING)
            ->whereDate('scheduled_date', '<=', Carbon::today())
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($reminders as $reminder) {
            $result['processed']++;

            // Revalida integridade da vacinacao e consentimento imediatamente antes de chamar a API
            $vac = $reminder->vaccination;
            $tutor = $reminder->tutor;

            if (!$vac || $vac->followup_status !== Vaccination::FOLLOWUP_PENDING) {
                $reminder->update([
                    'status' => VaccinationReminder::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_reason' => 'Vacinação já concluída ou cancelada antes do envio',
                ]);
                $result['cancelled']++;
                continue;
            }

            if ($reminder->reference_date && $vac->next_date && $vac->next_date->toDateString() !== $reminder->reference_date->toDateString()) {
                $reminder->update([
                    'status' => VaccinationReminder::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_reason' => 'Data prevista da vacinação foi alterada',
                ]);
                $result['cancelled']++;
                continue;
            }

            if (!$tutor || !$tutor->canReceiveAutoWhatsApp()) {
                $reminder->update([
                    'status' => VaccinationReminder::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancelled_reason' => 'Tutor sem autorização de WhatsApp ou telefone inválido',
                ]);
                $result['cancelled']++;
                continue;
            }

            $sent = $this->transmitReminder($reminder);
            if ($sent) {
                $result['sent_to_api']++;
            } else {
                $result['failed']++;
            }
        }

        Cache::forget(self::CACHE_STATS_KEY);
        return $result;
    }

    /**
     * Envio manual iniciado pelo funcionario (com mensagem pre-visualizada e confirmada).
     */
    public function sendManualWhatsApp(
        Vaccination $vaccination,
        Tutor $tutor,
        string $messageBody,
        int $userId,
        ?string $reminderType = null
    ): VaccinationReminder {
        $phone = $tutor->normalizedWhatsAppPhone();
        if (!$phone) {
            throw new \InvalidArgumentException('O tutor não possui um número de telefone válido com DDD para WhatsApp.');
        }

        $cleanBody = trim($messageBody);
        if ($cleanBody === '') {
            throw new \InvalidArgumentException('A mensagem não pode estar vazia.');
        }

        $type = $reminderType ?: VaccinationReminder::TYPE_MANUAL;
        $refDate = $vaccination->next_date ? $vaccination->next_date->toDateString() : Carbon::today()->toDateString();

        $reminder = VaccinationReminder::create([
            'vaccination_id' => $vaccination->id,
            'pet_id' => $vaccination->pet_id,
            'tutor_id' => $tutor->id,
            'reminder_type' => $type,
            'reference_date' => $refDate,
            'attempt_number' => 1,
            'idempotency_key' => 'manual:' . Str::uuid()->toString(),
            'scheduled_date' => Carbon::today()->toDateString(),
            'channel' => 'whatsapp',
            'destination' => $phone,
            'message_body' => $cleanBody,
            'status' => VaccinationReminder::STATUS_PENDING,
            'sent_by_user_id' => $userId,
            'branch_id' => $vaccination->branch_id ?? $vaccination->pet?->created_at_branch_id,
        ]);

        $this->transmitReminder($reminder);

        // Registra o horario do contato sem concluir a vacinacao!
        if ($reminder->status === VaccinationReminder::STATUS_SENT_TO_API) {
            $vaccination->update([
                'last_contact_at' => now(),
                'last_contact_notes' => 'WhatsApp enviado manualmente para ' . $tutor->name . ' (' . $phone . ')',
            ]);
        }

        return $reminder->fresh();
    }

    /**
     * Reprocessa um lembrete que falhou ou estava preparado aguardando liberacao.
     */
    public function reprocessReminder(VaccinationReminder $reminder, ?int $userId = null): VaccinationReminder
    {
        $reminder->load(['vaccination.pet', 'tutor']);
        $vac = $reminder->vaccination;

        if (!$vac || $vac->followup_status !== Vaccination::FOLLOWUP_PENDING) {
            $reminder->update([
                'status' => VaccinationReminder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_reason' => 'Cancelado no reprocessamento: vacinação já concluída ou cancelada',
            ]);
            return $reminder->fresh();
        }

        if ($userId) {
            $reminder->sent_by_user_id = $userId;
        }
        $reminder->status = VaccinationReminder::STATUS_PENDING;
        $reminder->error_message = null;
        $reminder->save();

        $this->transmitReminder($reminder);
        return $reminder->fresh();
    }

    /**
     * Executa o envio real atraves do NotificationService existente e registra em NotificationLog.
     * Nunca assume status "delivered" apenas pela aceitacao da API — registra "sent_to_api".
     */
    protected function transmitReminder(VaccinationReminder $reminder): bool
    {
        $destination = (string) $reminder->destination;
        $message = (string) $reminder->message_body;

        try {
            $sendResult = $this->notificationService->send(
                NotificationChannel::WhatsApp,
                $destination,
                $message
            );
        } catch (\Throwable $e) {
            Log::error('Vaccination reminder WhatsApp exception', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);
            $sendResult = \App\Services\Notification\NotificationResult::failed('exception', $e->getMessage());
        }

        if ($sendResult->success) {
            $reminder->update([
                'status' => VaccinationReminder::STATUS_SENT_TO_API,
                'sent_at' => now(),
                'provider' => $sendResult->provider,
                'provider_message_id' => $sendResult->messageId,
                'error_message' => null,
            ]);

            NotificationLog::create([
                'pet_id' => $reminder->pet_id,
                'tutor_id' => $reminder->tutor_id,
                'type' => 'vaccine_reminder_' . $reminder->reminder_type,
                'channel' => 'whatsapp',
                'destination' => $destination,
                'sent_at' => now(),
                'status' => VaccinationReminder::STATUS_SENT_TO_API,
                'message' => $message,
                'error_message' => null,
                'branch_id' => $reminder->branch_id,
            ]);

            return true;
        }

        $errorMsg = $sendResult->error ?? 'Erro desconhecido no provedor de WhatsApp.';
        $reminder->update([
            'status' => VaccinationReminder::STATUS_FAILED,
            'provider' => $sendResult->provider,
            'error_message' => $errorMsg,
        ]);

        NotificationLog::create([
            'pet_id' => $reminder->pet_id,
            'tutor_id' => $reminder->tutor_id,
            'type' => 'vaccine_reminder_' . $reminder->reminder_type,
            'channel' => 'whatsapp',
            'destination' => $destination,
            'sent_at' => now(),
            'status' => VaccinationReminder::STATUS_FAILED,
            'message' => $message,
            'error_message' => $errorMsg,
            'branch_id' => $reminder->branch_id,
        ]);

        return false;
    }

    /**
     * Ao registrar uma nova vacina aplicada, conclui automaticamente a pendencia anterior correspondente
     * e cancela todos os lembretes futuros relacionados.
     */
    public function autoCompletePreviousForNewVaccination(Vaccination $newVaccination, ?int $explicitPendingId = null): int
    {
        $completedCount = 0;

        if ($explicitPendingId) {
            $explicit = Vaccination::where('id', $explicitPendingId)
                ->where('pet_id', $newVaccination->pet_id)
                ->where('followup_status', Vaccination::FOLLOWUP_PENDING)
                ->first();

            if ($explicit) {
                $explicit->update([
                    'followup_status' => Vaccination::FOLLOWUP_COMPLETED,
                    'completed_by_vaccination_id' => $newVaccination->id,
                ]);
                $completedCount++;
            }
        }

        $targetFamily = $this->resolveFamilyKey($newVaccination);

        $openCandidates = Vaccination::where('pet_id', $newVaccination->pet_id)
            ->where('id', '!=', $newVaccination->id)
            ->where('followup_status', Vaccination::FOLLOWUP_PENDING)
            ->where(function ($q) use ($newVaccination) {
                $q->whereNull('date')
                  ->orWhereDate('date', '<=', $newVaccination->date);
            })
            ->get();

        foreach ($openCandidates as $cand) {
            if ($this->resolveFamilyKey($cand) === $targetFamily) {
                $cand->update([
                    'followup_status' => Vaccination::FOLLOWUP_COMPLETED,
                    'completed_by_vaccination_id' => $newVaccination->id,
                ]);
                $completedCount++;
            }
        }

        Cache::forget(self::CACHE_STATS_KEY);
        return $completedCount;
    }

    /**
     * Marca manualmente uma pendencia como cancelada (ex.: protocolo encerrado), cancelando lembretes futuros.
     */
    public function cancelPendingVaccination(Vaccination $vaccination, string $reason = 'Pendência cancelada pela clínica'): void
    {
        $vaccination->update([
            'followup_status' => Vaccination::FOLLOWUP_CANCELLED,
            'last_contact_notes' => trim(($vaccination->last_contact_notes ? $vaccination->last_contact_notes . "\n" : '') . $reason),
        ]);
    }

    /**
     * Registra contato realizado com o tutor (telefone/balcao/WhatsApp externo) sem concluir a vacinacao.
     */
    public function registerContact(Vaccination $vaccination, string $notes): void
    {
        $vaccination->update([
            'last_contact_at' => now(),
            'last_contact_notes' => trim($notes),
        ]);
    }

    protected function resolveFamilyKey(Vaccination $v): string
    {
        $cod = (string) ($v->sisvetw_cod_vac ?? '');
        if ($cod !== '' && isset($this->protocolFamilies[$cod])) {
            return $this->protocolFamilies[$cod];
        }

        $upper = mb_strtoupper(trim((string) $v->vaccine));
        if (str_contains($upper, 'V10') || str_contains($upper, 'V8')) {
            return 'V10';
        }
        if (str_contains($upper, 'GRIPE') || str_contains($upper, 'VANGUARD B')) {
            return 'GRIPE';
        }
        if (str_contains($upper, 'GIARDIA') || str_contains($upper, 'GIÁRDIA')) {
            return 'GIARDIA';
        }
        if (str_contains($upper, 'RAIVA') || str_contains($upper, 'ANTIRR')) {
            return 'RAIVA';
        }
        if (str_contains($upper, 'FELOCEL') && str_contains($upper, 'FELV')) {
            return 'FELOCELL_FELV';
        }
        if (str_contains($upper, 'FELOCEL') || str_contains($upper, 'QUADRUPLA')) {
            return 'FELOCEL_V4';
        }
        if (str_contains($upper, 'QUINTUPLA') || str_contains($upper, 'QUÍNTUPLA')) {
            return 'QUINTUPLA_FELINA';
        }
        if (str_contains($upper, 'VERMIF') || str_contains($upper, 'VERMÍF')) {
            return 'VERMIFUGO';
        }

        return $upper;
    }
}
