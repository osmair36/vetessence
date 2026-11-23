<?php

namespace Tests\Feature;

use App\Models\Appointment;
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
            'enable_vaccinations' => true,
            'remind_7_days_enabled' => true,
            'remind_2_days_enabled' => true,
            'remind_due_today_enabled' => true,
            'remind_overdue_enabled' => true,
            'enable_birthdays' => true,
            'enable_grooming' => true,
            'grooming_remind_1_day_enabled' => true,
            'grooming_remind_today_enabled' => true,
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

    public function test_2_days_before_birthday_and_grooming_notifications(): void
    {
        $fake = $this->makeFakeNotificationService(true, 'wa-multi-001');
        $service = new VaccinationNotificationCenterService($fake);
        $service->saveSettings([
            'auto_send_enabled' => true,
            'enable_vaccinations' => true,
            'remind_7_days_enabled' => false,
            'remind_2_days_enabled' => true,
            'remind_due_today_enabled' => false,
            'remind_overdue_enabled' => false,
            'enable_birthdays' => true,
            'enable_grooming' => true,
            'grooming_remind_1_day_enabled' => true,
            'grooming_remind_today_enabled' => true,
            'overdue_interval_days' => 7,
            'max_overdue_attempts' => 2,
            'overdue_max_age_days' => 180,
            'daily_run_time' => '09:00',
        ]);

        $vet = User::first();
        $tutor = Tutor::create([
            'name' => 'Tutor Multi Servicos',
            'phone' => '(44) 98888-2222',
            'whatsapp_opt_in' => true,
            'whatsapp_opt_in_at' => now(),
            'notify_whatsapp' => true,
        ]);

        $today = Carbon::today();
        $pet = Pet::create([
            'name' => 'Luna Aniversariante',
            'species' => 'canine',
            'gender' => 'female',
            'birth_date' => $today->copy()->subYears(3)->toDateString(),
            'is_active' => true,
        ]);
        $pet->tutors()->attach($tutor->id, ['is_primary' => true, 'relationship' => 'Tutor']);

        $vac2Days = Vaccination::create([
            'pet_id' => $pet->id,
            'vet_id' => $vet->id,
            'vaccine' => 'ANTIRRABICA',
            'date' => $today->copy()->subYear(),
            'next_date' => $today->copy()->addDays(2),
            'followup_status' => Vaccination::FOLLOWUP_PENDING,
        ]);

        $groomingAppt = Appointment::create([
            'pet_id' => $pet->id,
            'vet_id' => $vet->id,
            'date' => $today->copy()->addDay()->toDateString(),
            'time' => '14:30',
            'type' => 'grooming',
            'status' => 'scheduled',
            'reason' => 'Banho e Tosa Higiênica',
        ]);

        $prep = $service->prepareReminders(false, $today);

        $this->assertGreaterThanOrEqual(1, $prep['checked_2_days']);
        $this->assertGreaterThanOrEqual(1, $prep['checked_birthdays']);
        $this->assertGreaterThanOrEqual(1, $prep['checked_grooming_1_day']);

        $this->assertTrue(VaccinationReminder::where('vaccination_id', $vac2Days->id)
            ->where('reminder_type', VaccinationReminder::TYPE_BEFORE_2_DAYS)->exists());
        $this->assertTrue(VaccinationReminder::where('pet_id', $pet->id)
            ->where('category', VaccinationReminder::CATEGORY_BIRTHDAY)->exists());
        $this->assertTrue(VaccinationReminder::where('appointment_id', $groomingAppt->id)
            ->where('category', VaccinationReminder::CATEGORY_GROOMING)->exists());

        $disp = $service->dispatchPendingReminders(50, true);
        $this->assertGreaterThanOrEqual(3, $disp['sent_to_api']);
    }
}
