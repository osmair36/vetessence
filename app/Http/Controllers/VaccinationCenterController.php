<?php

namespace App\Http\Controllers;

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
        $msgStatus = $request->get('msg_status', 'all');
        $tutorFilter = $request->get('tutor_filter', 'invalid_phone');
        $search = trim((string) $request->get('search', ''));

        $stats = $this->service->getDashboardStats();
        $settings = $this->service->getSettings();
        $canManageSettings = Gate::allows('configuracoes');

        $vaccinations = null;
        $reminders = null;
        $tutors = null;

        if ($tab === 'acompanhamento') {
            $vaccinations = $this->service->getVaccinationsByBucket($bucket, $search ?: null, 25);
        } elseif ($tab === 'mensagens') {
            $q = VaccinationReminder::with(['vaccination', 'pet', 'tutor', 'sentByUser'])
                ->orderByDesc('id');

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
            'msgStatus',
            'tutorFilter',
            'search',
            'stats',
            'settings',
            'canManageSettings',
            'vaccinations',
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
     * Salva configuracoes gerais e modelos de mensagens (restrito a administradores).
     */
    public function updateSettings(Request $request)
    {
        Gate::authorize('configuracoes');

        $validated = $request->validate([
            'auto_send_enabled' => 'nullable|boolean',
            'remind_7_days_enabled' => 'nullable|boolean',
            'remind_due_today_enabled' => 'nullable|boolean',
            'remind_overdue_enabled' => 'nullable|boolean',
            'overdue_interval_days' => 'required|integer|min:1|max:90',
            'max_overdue_attempts' => 'required|integer|min:1|max:20',
            'overdue_max_age_days' => 'required|integer|min:7|max:1825',
            'daily_run_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'template_7_days' => 'required|string|min:10|max:4000',
            'template_due_today' => 'required|string|min:10|max:4000',
            'template_overdue' => 'required|string|min:10|max:4000',
        ]);

        $this->service->saveSettings([
            'auto_send_enabled' => $request->boolean('auto_send_enabled'),
            'remind_7_days_enabled' => $request->boolean('remind_7_days_enabled'),
            'remind_due_today_enabled' => $request->boolean('remind_due_today_enabled'),
            'remind_overdue_enabled' => $request->boolean('remind_overdue_enabled'),
            'overdue_interval_days' => $validated['overdue_interval_days'],
            'max_overdue_attempts' => $validated['max_overdue_attempts'],
            'overdue_max_age_days' => $validated['overdue_max_age_days'],
            'daily_run_time' => $validated['daily_run_time'],
            'template_7_days' => $validated['template_7_days'],
            'template_due_today' => $validated['template_due_today'],
            'template_overdue' => $validated['template_overdue'],
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
            ? "Simulação concluída: {$prep['prepared']} lembrete(s) elegível(is) (7d={$prep['checked_7_days']}, hoje={$prep['checked_due_today']}, atraso={$prep['checked_overdue']}, sem opt-in={$prep['skipped_no_opt_in']}, duplicados={$prep['skipped_duplicate']})."
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
