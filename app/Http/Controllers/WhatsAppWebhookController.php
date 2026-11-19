<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\VaccinationReminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Recebe confirmacoes assincronas de entrega/leitura/falha dos provedores de WhatsApp
     * (Z-API, Meta Cloud API, Twilio).
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $payload = $request->all();
        Log::info("WhatsApp webhook received [{$provider}]", ['payload' => $payload]);

        $updates = match (strtolower($provider)) {
            'zapi' => $this->parseZapi($payload),
            'cloudapi', 'meta' => $this->parseCloudApi($payload),
            'twilio' => $this->parseTwilio($payload),
            default => [],
        };

        $matched = 0;
        foreach ($updates as $upd) {
            if (empty($upd['message_id']) || empty($upd['status'])) {
                continue;
            }

            $reminder = VaccinationReminder::where('provider_message_id', $upd['message_id'])->first();
            if (!$reminder) {
                continue;
            }

            $data = [];
            if ($upd['status'] === VaccinationReminder::STATUS_DELIVERED && $reminder->status !== VaccinationReminder::STATUS_READ) {
                $data['status'] = VaccinationReminder::STATUS_DELIVERED;
                $data['delivered_at'] = $reminder->delivered_at ?? now();
            } elseif ($upd['status'] === VaccinationReminder::STATUS_READ) {
                $data['status'] = VaccinationReminder::STATUS_READ;
                $data['delivered_at'] = $reminder->delivered_at ?? now();
                $data['read_at'] = now();
            } elseif ($upd['status'] === VaccinationReminder::STATUS_FAILED) {
                $data['status'] = VaccinationReminder::STATUS_FAILED;
                $data['error_message'] = $upd['error'] ?? 'Falha reportada via webhook pelo provedor.';
            }

            if (!empty($data)) {
                $reminder->update($data);
                NotificationLog::where('pet_id', $reminder->pet_id)
                    ->where('tutor_id', $reminder->tutor_id)
                    ->where('channel', 'whatsapp')
                    ->latest('id')
                    ->limit(1)
                    ->update(['status' => $data['status']]);
                $matched++;
            }
        }

        return response()->json(['ok' => true, 'updated' => $matched]);
    }

    protected function parseZapi(array $payload): array
    {
        $ids = $payload['ids'] ?? [];
        if (empty($ids) && !empty($payload['zaapId'])) {
            $ids = [$payload['zaapId']];
        } elseif (empty($ids) && !empty($payload['messageId'])) {
            $ids = [$payload['messageId']];
        }

        $rawStatus = strtoupper((string) ($payload['status'] ?? ''));
        $mapped = match ($rawStatus) {
            'RECEIVED', 'DELIVERED' => VaccinationReminder::STATUS_DELIVERED,
            'READ', 'PLAYED' => VaccinationReminder::STATUS_READ,
            'FAILED', 'ERROR' => VaccinationReminder::STATUS_FAILED,
            default => null,
        };

        if (!$mapped || empty($ids)) {
            return [];
        }

        return array_map(fn ($id) => ['message_id' => (string) $id, 'status' => $mapped, 'error' => $payload['error'] ?? null], (array) $ids);
    }

    protected function parseCloudApi(array $payload): array
    {
        $results = [];
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                foreach (($change['value']['statuses'] ?? []) as $st) {
                    $mapped = match (strtolower((string) ($st['status'] ?? ''))) {
                        'delivered' => VaccinationReminder::STATUS_DELIVERED,
                        'read' => VaccinationReminder::STATUS_READ,
                        'failed' => VaccinationReminder::STATUS_FAILED,
                        default => null,
                    };
                    if ($mapped && !empty($st['id'])) {
                        $results[] = [
                            'message_id' => (string) $st['id'],
                            'status' => $mapped,
                            'error' => $st['errors'][0]['title'] ?? null,
                        ];
                    }
                }
            }
        }
        return $results;
    }

    protected function parseTwilio(array $payload): array
    {
        $sid = $payload['MessageSid'] ?? $payload['SmsSid'] ?? null;
        $mapped = match (strtolower((string) ($payload['MessageStatus'] ?? $payload['SmsStatus'] ?? ''))) {
            'delivered' => VaccinationReminder::STATUS_DELIVERED,
            'read' => VaccinationReminder::STATUS_READ,
            'failed', 'undelivered' => VaccinationReminder::STATUS_FAILED,
            default => null,
        };

        if (!$sid || !$mapped) {
            return [];
        }

        return [[
            'message_id' => (string) $sid,
            'status' => $mapped,
            'error' => $payload['ErrorMessage'] ?? null,
        ]];
    }
}
