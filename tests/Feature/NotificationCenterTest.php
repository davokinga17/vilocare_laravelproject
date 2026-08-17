<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_button_is_available_on_non_dashboard_pages_for_support_users(): void
    {
        $user = User::query()->create([
            'name' => 'Clinician User',
            'username' => 'clinician_user',
            'role' => 'Clinician',
            'email' => 'clinician@example.com',
            'password' => bcrypt('password'),
            'must_change_password' => false,
        ]);

        $patient = Patient::query()->create([
            'art_number' => 'EE-TR-0200',
            'first_name' => 'Asha',
            'last_name' => 'Deng',
            'sex' => 'Female',
            'phone' => '+211900000200',
        ]);

        Appointment::query()->create([
            'patient_id' => $patient->patient_id,
            'appointment_date' => '2026-08-20',
            'reason' => 'Follow-up review',
            'status' => 'Pending',
        ]);

        NotificationLog::query()->create([
            'channel' => 'sms',
            'category' => 'appointment_reminder',
            'status' => 'sent',
            'recipient' => '+211900000200',
            'provider' => 'log',
            'message' => 'Reminder sent.',
        ]);

        $this->actingAs($user);
        $response = $this->view('profile.edit', [
            'user' => $user,
            'errors' => new ViewErrorBag(),
        ]);

        $response->assertSee('id="globalNotifications"', false);
        $response->assertSee('Notifications');
        $response->assertSee('Upcoming appointment');
    }
}
