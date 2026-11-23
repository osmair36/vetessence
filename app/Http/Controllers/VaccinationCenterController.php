<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Tutor;
use App\Models\Vaccination;
use App\Models\VaccinationReminder;
use App\Services\Vaccination\VaccinationNotificationCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VaccinationCenterController extends Controller
{
    public function __construct(
        protected VaccinationNotificationCenterService $service,
    ) {
        $this->middleware('can:lembrete-vacinas');
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'acompanhamento');
        $bucket = $request->get('bucket', 'due_today');
        $bdayBucket = $request->get('bday_bucket', 'today');
        $groomBucket = $request->get('groom_bucket', 'today');
        $msgStatus = $request->get('msg_status', 'all');
        $msgCategory = $request->get('msg_category', 'all');
        $tutorFilter = $request->get('tutor_filter', 'invalid_phone');
        $search = trim((string) $request->get('search', ''));

        $stats = $this->service->getDashboardStats();
        $settings = $this->service->getSettings();
        $canManageSettings = Gate::allows('configuracoes');

        $vaccinations = null;
        $birthdays = null;
        $groomings = null;
        $reminders = null;
        $tutors = null;

        if ($tab === 'acompanhamento') {
            $vaccinations = $this->service->getVaccinationsByBucket($bucket, $search ?: null, 25);
        } elseif ($tab === 'aniversariantes') {
            $birthdays = $this->service->getBirthdays($bdayBucket, $search ?: null, 25);
        } elseif ($tab === 'banho_tosa') {
            $groomings = $this->service->getGroomingAppointments($groomBucket, $search ?: null, 25);
        } elseif ($tab === 'mensagens') {
            $q = VaccinationReminder::with(['vaccination', 'appointment', 'pet', 'tutor', 'sentByUser'])
                ->orderByDesc('id');

            if (in_array($msgCategory, [
                VaccinationReminder::CATEGORY_VACCINATION,
                VaccinationReminder::CATEGORY_BIRTHDAY,
                VaccinationReminder::CATEGORY_GROOMING,
            ], true)) {
                $q->where('category', $msgCategory);
            }

            if ($msgStatus === 'sent') {
                $q->whereIn('status', [
                    VaccinationReminder::STATUS_SENT_TO_API,
                    'sent',
                    VaccinationReminder::STATUS_DELIVERED,
                    VaccinationReminder::STATUS_READ,
                ]);
            } elseif ($msgStatus === 'pending') {
                $q->whereIn('status', [VaccinationReminder::STATUS_PREPARED, VaccinationReminder::STATUS_PENDING]);
            } elseif ($msgStatus === 'failed') {
                $q->where('status', VaccinationReminder::STATUS_FAILED);
            } elseif ($msgStatus === 'cancelled') {
                $q->where('status', VaccinationReminder::STATUS_CANCELLED);
            }

            if ($search !== '') {
                $q->where(function ($sub) use ($search) {
                    $sub->where('destination', 'like', "%{$search}%")
                        ->orWhere('message_body', 'like', "%{$search}%")
                        ->orWhereHas('pet', fn ($pq) => $pq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('tutor', fn ($tq) => $tq->where('name', 'like', "%{$search}%"));
                });
            }

            $reminders = $q->paginate(25);
        } elseif ($tab === 'tutores') {
            $q = Tutor::withCount('pets')->orderBy('name');

            if ($tutorFilter === 'invalid_phone') {
                $q->where(function ($sub) {
                    $sub->whereNull('phone')
                        ->orWhere('phone', '')
                        ->orWhere('phone', 'Não informado')
                        ->orWhereRaw("length(replace(replace(replace(replace(replace(phone,'(',''),')',''),'-',''),' ',''),'+','')) < 10");
                });
            } elseif ($tutorFilter === 'no_opt_in') {
                $q->whereNull('whatsapp_opt_in');
            } elseif ($tutorFilter === 'opt_out') {
                $q->where('whatsapp_opt_in', false);
            } elseif ($tutorFilter === 'authorized') {
                $q->where('whatsapp_opt_in', true);
            }

            if ($search !== '') {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%");
                });
            }

            $tutors = $q->paginate(25);
        }

        return view('vaccination-center.index', compact(
            'tab',
            'bucket',
            'bdayBucket',
            'groomBucket',
            'msgStatus',
            'msgCategory',
            'tutorFilter',
            'search',
            'stats',
            'settings',
            'canManageSettings',
            'vaccinations',
            'birthdays',
            'groomings',
            'reminders',
            'tutors'
        ));
    }

    /**
     * Retorna pre-visualizacao da mensagem WhatsApp para confirmacao antes do envio manual.
     */
    public function preview(Request $request, Vaccination $vaccination): JsonResponse
    {
        $vaccination->load(['pet.tutors']);
        $tutors = $vaccination->pet?->tutors ?? collect();
        $tutorId = $request->get('tutor_id');
        $tutor = $tutorId ? $tutors->firstWhere('id', (int) $tutorId) : $tutors->first();

        if (!$tutor) {
            return response()->json([
                'ok' => false,
                'error' => 'Este animal não possui tutor vinculado.',
            ], 422);
        }

        $type = $request->get('type') ?: $this->service->resolveReminderTypeForVaccination($vaccination);
        $rendered = $this->service->renderMessage($vaccination, $tutor, $type);

        return response()->json([
            'ok' => $rendered['ok'],
            'error' => $rendered['error'],
            'vaccination_id' => $vaccination->id,
            'tutor_id' => $tutor->id,
            'tutor_name' => $tutor->name,
            'phone_raw' => $tutor->phone,
            'phone_normalized' => $tutor->normalizedWhatsAppPhone(),
            'has_valid_phone' => $tutor->hasValidWhatsAppPhone(),
            'whatsapp_opt_in' => $tutor->whatsapp_opt_in,
            'pet_name' => $vaccination->pet?->name,
            'vaccine' => $vaccination->vaccine,
            'laboratory' => $vaccination->laboratory,
            'next_date' => $vaccination->next_date ? $vaccination->next_date->format('d/m/Y') : null,
            'reminder_type' => $type,
            'message' => $rendered['message'] ?? '',
            'tutors' => $tutors->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'valid_phone' => $t->hasValidWhatsAppPhone(),
                'opt_in' => $t->whatsapp_opt_in,
            ])->values(),
        ]);
    }

    /**
     * Envio manual confirmado pelo funcionario atraves da integracao WhatsApp existente.
     */
    public function sendManual(Request $request, Vaccination $vaccination)
    {
        $validated = $request->validate([
            'tutor_id' => 'required|exists:tutors,id',
            'message_body' => 'required|string|min:5|max:4000',
            'reminder_type' => 'nullable|string|max:30',
        ]);

        $tutor = Tutor::findOrFail($validated['tutor_id']);

        try {
            $reminder = $this->service->sendManualWhatsApp(
                $vaccination,
                $tutor,
                $validated['message_body'],
                (int) auth()->id(),
                $validated['reminder_type'] ?? null
            );

            if ($reminder->status === VaccinationReminder::STATUS_SENT_TO_API) {
                return back()->with('success', "Mensagem WhatsApp enviada à API ({$reminder->provider}) para {$tutor->name}!");
            }

            return back()->with('error', 'Falha ao enviar WhatsApp: ' . ($reminder->error_message ?? 'Erro desconhecido.'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra contato realizado com o tutor (sem marcar a vacina como aplicada).
     */
    public function registerContact(Request $request, Vaccination $vaccination)
    {
        $validated = $request->validate([
            'contact_notes' => 'required|string|max:1000',
        ]);

        $this->service->registerContact($vaccination, $validated['contact_notes']);

        return back()->with('success', 'Contato com o tutor registrado com sucesso.');
    }

    /**
     * Cancela pendencia e seus lembretes futuros.
     */
    public function cancelPending(Request $request, Vaccination $vaccination)
    {
        $reason = trim((string) $request->input('reason', 'Pendência cancelada pela clínica'));
        $this->service->cancelPendingVaccination($vaccination, $reason);

        return back()->with('success', 'Pendência cancelada e lembretes futuros removidos da fila.');
    }

    /**
     * Reprocessa uma mensagem com falha ou preparada.
     */
    public function reprocess(VaccinationReminder $vaccinationReminder)
    {
        $updated = $this->service->reprocessReminder($vaccinationReminder, (int) auth()->id());

        if ($updated->status === VaccinationReminder::STATUS_SENT_TO_API) {
            return back()->with('success', 'Mensagem reprocessada e enviada à API com sucesso!');
        }
        if ($updated->status === VaccinationReminder::STATUS_CANCELLED) {
            return back()->with('info', 'Lembrete cancelado automaticamente: ' . ($updated->cancelled_reason ?? 'vacinação já concluída.'));
        }

        return back()->with('error', 'Reprocessamento falhou: ' . ($updated->error_message ?? 'Erro desconhecido.'));
    }

    /**
     * Registra autorizacao explicita (opt-in) ou recusa (opt-out) do tutor para WhatsApp.
     */
    public function updateTutorOptIn(Request $request, Tutor $tutor)
    {
        $validated = $request->validate([
            'opt_in' => 'required|in:0,1',
        ]);

        $authorized = $validated['opt_in'] === '1';
        $tutor->update([
            'whatsapp_opt_in' => $authorized,
            'notify_whatsapp' => $authorized,
            'whatsapp_opt_in_at' => $authorized ? now() : $tutor->whatsapp_opt_in_at,
            'whatsapp_opt_out_at' => $authorized ? null : now(),
            'whatsapp_opt_in_source' => $authorized ? 'central_optin' : 'central_optout',
        ]);

        return back()->with('success', $authorized
            ? "Autorização de WhatsApp registrada para {$tutor->name}."
            : "Opção de não receber mensagens registrada para {$tutor->name}.");
    }

    /**
     * Pre-visualizacao da mensagem WhatsApp de Aniversariante do Dia (Pet).
     */
    public function previewBirthday(Request $request, Pet $pet): JsonResponse
    {
        $pet->load(['tutors']);
        $tutors = $pet->tutors ?? collect();
        $tutorId = $request->get('tutor_id');
        $tutor = $tutorId ? $tutors->firstWhere('id', (int) $tutorId) : $tutors->first();

        if (!$tutor) {
            return response()->json([
                'ok' => false,
                'error' => 'Este animal não possui tutor vinculado.',
            ], 422);
        }

        $rendered = $this->service->renderBirthdayMessage($pet, $tutor);

        return response()->json([
            'ok' => $rendered['ok'],
            'error' => $rendered['error'],
            'pet_id' => $pet->id,
            'tutor_id' => $tutor->id,
            'tutor_name' => $tutor->name,
            'phone_raw' => $tutor->phone,
            'phone_normalized' => $tutor->normalizedWhatsAppPhone(),
            'has_valid_phone' => $tutor->hasValidWhatsAppPhone(),
            'whatsapp_opt_in' => $tutor->whatsapp_opt_in,
            'pet_name' => $pet->name,
            'vaccine' => 'Aniversariante do Dia 🎂',
            'laboratory' => $pet->breed ?: null,
            'next_date' => $pet->birth_date ? $pet->birth_date->format('d/m/Y') : '-',
            'reminder_type' => VaccinationReminder::TYPE_BIRTHDAY_MANUAL,
            'message' => $rendered['message'] ?? '',
            'tutors' => $tutors->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'valid_phone' => $t->hasValidWhatsAppPhone(),
                'opt_in' => $t->whatsapp_opt_in,
            ])->values(),
        ]);
    }

    /**
     * Envio manual de felicitacao de aniversario via WhatsApp.
     */
    public function sendManualBirthday(Request $request, Pet $pet)
    {
        $validated = $request->validate([
            'tutor_id' => 'required|exists:tutors,id',
            'message_body' => 'required|string|min:5|max:4000',
        ]);

        $tutor = Tutor::findOrFail($validated['tutor_id']);

        try {
            $reminder = $this->service->sendManualBirthdayWhatsApp(
                $pet,
                $tutor,
                $validated['message_body'],
                (int) auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($reminder->status === VaccinationReminder::STATUS_SENT_TO_API) {
            return back()->with('success', "Mensagem de aniversário enviada à API do WhatsApp para {$tutor->name} ({$reminder->destination}).");
        }

        return back()->with('error', 'Falha no envio do WhatsApp: ' . ($reminder->error_message ?: 'Verifique a configuração do provedor.'));
    }

    /**
     * Pre-visualizacao da mensagem WhatsApp de Agendamento de Banho e Tosa.
     */
    public function previewGrooming(Request $request, Appointment $appointment): JsonResponse
    {
        $appointment->load(['pet.tutors', 'groomer', 'vet', 'services.service']);
        $tutors = $appointment->pet?->tutors ?? collect();
        $tutorId = $request->get('tutor_id');
        $tutor = $tutorId ? $tutors->firstWhere('id', (int) $tutorId) : $tutors->first();

        if (!$tutor) {
            return response()->json([
                'ok' => false,
                'error' => 'Este animal não possui tutor vinculado.',
            ], 422);
        }

        $rendered = $this->service->renderGroomingMessage($appointment, $tutor);

        $serviceNames = $appointment->services->map(fn ($as) => $as->service?->name)->filter()->implode(', ');
        $timeFormatted = $appointment->time
            ? (is_string($appointment->time) ? substr($appointment->time, 0, 5) : $appointment->time->format('H:i'))
            : '';

        return response()->json([
            'ok' => $rendered['ok'],
            'error' => $rendered['error'],
            'appointment_id' => $appointment->id,
            'tutor_id' => $tutor->id,
            'tutor_name' => $tutor->name,
            'phone_raw' => $tutor->phone,
            'phone_normalized' => $tutor->normalizedWhatsAppPhone(),
            'has_valid_phone' => $tutor->hasValidWhatsAppPhone(),
            'whatsapp_opt_in' => $tutor->whatsapp_opt_in,
            'pet_name' => $appointment->pet?->name,
            'vaccine' => $serviceNames ?: ($appointment->reason ?: 'Banho e Tosa'),
            'laboratory' => $appointment->groomer?->name ?: null,
            'next_date' => ($appointment->date ? $appointment->date->format('d/m/Y') : '-') . ($timeFormatted ? " às {$timeFormatted}" : ''),
            'reminder_type' => VaccinationReminder::TYPE_GROOMING_MANUAL,
            'message' => $rendered['message'] ?? '',
            'tutors' => $tutors->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'phone' => $t->phone,
                'valid_phone' => $t->hasValidWhatsAppPhone(),
                'opt_in' => $t->whatsapp_opt_in,
            ])->values(),
        ]);
    }

    /**
     * Envio manual de lembrete de Banho e Tosa via WhatsApp.
     */
    public function sendManualGrooming(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'tutor_id' => 'required|exists:tutors,id',
            'message_body' => 'required|string|min:5|max:4000',
        ]);

        $tutor = Tutor::findOrFail($validated['tutor_id']);

        try {
            $reminder = $this->service->sendManualGroomingWhatsApp(
                $appointment,
                $tutor,
                $validated['message_body'],
                (int) auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($reminder->status === VaccinationReminder::STATUS_SENT_TO_API) {
            return back()->with('success', "Lembrete de Banho e Tosa enviado à API do WhatsApp para {$tutor->name} ({$reminder->destination}).");
        }

        return back()->with('error', 'Falha no envio do WhatsApp: ' . ($reminder->error_message ?: 'Verifique a configuração do provedor.'));
    }

    /**
     * Salva configuracoes gerais e modelos de mensagens (restrito a administradores).
     */
    public function updateSettings(Request $request)
    {
        Gate::authorize('configuracoes');

        $validated = $request->validate([
            'auto_send_enabled' => 'nullable|boolean',
            'enable_vaccinations' => 'nullable|boolean',
            'remind_7_days_enabled' => 'nullable|boolean',
            'remind_2_days_enabled' => 'nullable|boolean',
            'remind_due_today_enabled' => 'nullable|boolean',
            'remind_overdue_enabled' => 'nullable|boolean',
            'enable_birthdays' => 'nullable|boolean',
            'enable_grooming' => 'nullable|boolean',
            'grooming_remind_1_day_enabled' => 'nullable|boolean',
            'grooming_remind_today_enabled' => 'nullable|boolean',
            'overdue_interval_days' => 'required|integer|min:1|max:90',
            'max_overdue_attempts' => 'required|integer|min:1|max:20',
            'overdue_max_age_days' => 'required|integer|min:7|max:1825',
            'daily_run_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'template_7_days' => 'required|string|min:10|max:4000',
            'template_2_days' => 'required|string|min:10|max:4000',
            'template_due_today' => 'required|string|min:10|max:4000',
            'template_overdue' => 'required|string|min:10|max:4000',
            'template_birthday' => 'required|string|min:10|max:4000',
            'template_grooming' => 'required|string|min:10|max:4000',
        ]);

        $this->service->saveSettings([
            'auto_send_enabled' => $request->boolean('auto_send_enabled'),
            'enable_vaccinations' => $request->boolean('enable_vaccinations'),
            'remind_7_days_enabled' => $request->boolean('remind_7_days_enabled'),
            'remind_2_days_enabled' => $request->boolean('remind_2_days_enabled'),
            'remind_due_today_enabled' => $request->boolean('remind_due_today_enabled'),
            'remind_overdue_enabled' => $request->boolean('remind_overdue_enabled'),
            'enable_birthdays' => $request->boolean('enable_birthdays'),
            'enable_grooming' => $request->boolean('enable_grooming'),
            'grooming_remind_1_day_enabled' => $request->boolean('grooming_remind_1_day_enabled'),
            'grooming_remind_today_enabled' => $request->boolean('grooming_remind_today_enabled'),
            'overdue_interval_days' => $validated['overdue_interval_days'],
            'max_overdue_attempts' => $validated['max_overdue_attempts'],
            'overdue_max_age_days' => $validated['overdue_max_age_days'],
            'daily_run_time' => $validated['daily_run_time'],
            'template_7_days' => $validated['template_7_days'],
            'template_2_days' => $validated['template_2_days'],
            'template_due_today' => $validated['template_due_today'],
            'template_overdue' => $validated['template_overdue'],
            'template_birthday' => $validated['template_birthday'],
            'template_grooming' => $validated['template_grooming'],
        ]);

        return redirect()->route('vaccination-center.index', ['tab' => 'configuracoes'])
            ->with('success', 'Configurações da Central de Lembretes e modelos de mensagens salvos com sucesso!');
    }

    /**
     * Executa a rotina diaria manualmente (modo simulacao ou real) com permissao administrativa.
     */
    public function runNow(Request $request)
    {
        Gate::authorize('configuracoes');

        $dryRun = $request->boolean('dry_run', true);
        $prep = $this->service->prepareReminders($dryRun);
        $msg = $dryRun
            ? "Simulação concluída: {$prep['prepared']} lembrete(s) elegível(is) (Vacinas 7d={$prep['checked_7_days']}, 2d={$prep['checked_2_days']}, hoje={$prep['checked_due_today']}, atraso={$prep['checked_overdue']} | Aniversariantes={$prep['checked_birthdays']} | Banho/Tosa 1d={$prep['checked_grooming_1_day']}, hoje={$prep['checked_grooming_today']} | sem opt-in={$prep['skipped_no_opt_in']}, duplicados={$prep['skipped_duplicate']})."
            : "Rotina executada: {$prep['prepared']} novo(s) lembrete(s) preparado(s).";

        if (!$dryRun) {
            $disp = $this->service->dispatchPendingReminders(100, true);
            $msg .= $disp['auto_send_disabled']
                ? ' Envio automático desativado (mensagens mantidas como preparadas).'
                : " Enviadas à API: {$disp['sent_to_api']} | Falhas: {$disp['failed']} | Canceladas: {$disp['cancelled']}.";
        }

        return redirect()->route('vaccination-center.index', ['tab' => 'mensagens'])
            ->with('success', $msg);
    }
}
