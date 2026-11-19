<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\VaccinationReminder;
use App\Services\Notification\NotificationChannel;
use App\Services\Notification\NotificationResult;
use App\Services\Notification\NotificationService;
use App\Services\Vaccination\VaccinationNotificationCenterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VaccinationNotificationCenterTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeFakeNotificationService(bool $shouldSucceed = true, ?string $messageId = 'msg-123'): NotificationService
    {
        return new class($shouldSucceed, $messageId) extends NotificationService {
            public array $sentCalls = [];
            public function __construct(public bool $ok, public ?string $msgId) {}
            public function send(NotificationChannel $channel, string $destination, string $message, ?string $subject = null, array $attachments = []): NotificationResult
            {
                $this->sentCalls[] = compact('channel', 'destination', 'message');
                return $this->ok
                    ? NotificationResult::success('MockWhatsApp', $this->msgId)
                    : NotificationResult::failed('MockWhatsApp', 'Simulated provider outage');
            }
        };
    }

    public function test_all_core_vaccination_notification_center_scenarios(): void
    {
        $fake = $this->makeFakeNotificationService(true, 'zaap-001');
        $service = new VaccinationNotificationCenterService($fake);
        $service->saveSettings([
            'auto_send_enabled' => true,
            'remind_7_days_enabled' => true,
            'remind_due_today_enabled' => true,
            'remind_overdue_enabled' => true,
            'overdue_interval_days' => 7,
            'max_overdue_attempts' => 2,
            'overdue_max_age_days' => 180,
            'daily_run_time' => '09:00',
        ]);

        $vet = User::first();
        $tutor = Tutor::create([
            'name' => 'Tutor Teste OptIn',
            'phone' => '(44) 99999-1111',
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now(),
            'notify_whatsapp' => true,
        ]);

        $pet = Pet::create([
            'name' => 'Rex Teste',
            'species' => 'canine',
            'gender' => 'male',
            'is_active' => true,
        ]);
        $pet->tutors()->attach($tutor->id, ['is_primary' => true, 'relationship' => 'Tutor']);

        $today = Carbon::today();
        $vacToday = Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $vet->id,
            'vaccine' => 'V10 ADULTO',
            'laboratory' => 'ZOETIS',
            'date' => $today->copy()->subYear(),
            'next_date' => $today,
            'followup_status' => Vaccination::FOLLOWUP_PENDING,
        ]);

        $prep1 = $service->prepareReminders(false, $today);
        $prep2 = $service->prepareReminders(false, $today);

        $this->assertGreaterThanOrEqual(1, $prep1['prepared']);
        $this->assertEquals(0, $prep2['prepared']);
        $this->assertEquals(1, VaccinationReminder::where('vaccination_id', $vacToday->id)->count());

        $disp = $service->dispatchPendingReminders(50, true);
        $this->assertGreaterThanOrEqual(1, $disp['sent_to_api']);
        $this->assertEquals(VaccinationReminder::STATUS_SENT_TO_API, VaccinationReminder::where('vaccination_id', $vacToday->id)->first()->status);
    }
}
