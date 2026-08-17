<?php

namespace Tests\Feature;

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ManualSmsController;
use App\Http\Controllers\ViralLoadController;
use App\Models\EACSession;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\User;
use App\Models\ViralLoad;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SmsReminderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vilocare.sms.driver', 'log');
        $this->withoutMiddleware();
    }

    public function test_suppressed_viral_load_result_sends_patient_sms(): void
    {
        $patient = $this->createPatient();
        $viralLoad = ViralLoad::query()->create([
            'patient_id' => $patient->patient_id,
            'result_cpml' => 250,
            'result_date' => '2026-08-12',
            'sample_date' => '2026-08-10',
        ]);

        app(NotificationService::class)->sendViralLoadResultMessage($viralLoad);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'category' => 'viral_load_result_suppressed',
            'recipient' => '+211923546133',
            'status' => 'sent',
            'provider' => 'log',
        ]);
    }

    public function test_unsuppressed_viral_load_result_sends_follow_up_sms(): void
    {
        $patient = $this->createPatient();
        $viralLoad = ViralLoad::query()->create([
            'patient_id' => $patient->patient_id,
            'result_cpml' => 1800,
            'result_date' => '2026-08-12',
            'sample_date' => '2026-08-10',
        ]);

        app(NotificationService::class)->sendViralLoadResultMessage($viralLoad);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'category' => 'viral_load_result_unsuppressed',
            'recipient' => '+211923546133',
            'status' => 'sent',
            'provider' => 'log',
        ]);
    }

    public function test_vl_due_command_sends_one_sms_per_due_patient_per_day(): void
    {
        Carbon::setTestNow('2026-08-12 09:00:00');

        $patient = $this->createPatient();

        EACSession::query()->create([
            'patient_id' => $patient->patient_id,
            'session_number' => 3,
            'session_date' => '2026-08-01',
            'next_session_date' => '2026-08-12',
            'completion_status' => 'Completed',
        ]);

        $this->artisan('vilocare:send-vl-due-reminders --fresh-only')
            ->expectsOutput('Processed 1 VL due reminder(s).')
            ->assertExitCode(0);

        $this->artisan('vilocare:send-vl-due-reminders --fresh-only')
            ->expectsOutput('Processed 1 VL due reminder(s).')
            ->assertExitCode(0);

        $sentCount = NotificationLog::query()
            ->where('channel', 'sms')
            ->where('category', 'vl_due_reminder')
            ->where('status', 'sent')
            ->count();

        $this->assertSame(1, $sentCount);
    }

    public function test_eac_due_command_sends_sms_to_pending_due_session(): void
    {
        Carbon::setTestNow('2026-08-12 09:00:00');

        $patient = $this->createPatient();

        EACSession::query()->create([
            'patient_id' => $patient->patient_id,
            'session_number' => 2,
            'session_date' => '2026-08-12',
            'completion_status' => 'Pending',
        ]);

        $this->artisan('vilocare:send-eac-due-reminders --fresh-only')
            ->expectsOutput('Processed 1 EAC due reminder(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'category' => 'eac_due_reminder',
            'recipient' => '+211923546133',
            'status' => 'sent',
            'provider' => 'log',
        ]);
    }

    public function test_creating_suppressed_viral_load_does_not_auto_send_patient_sms(): void
    {
        $user = $this->createUser('Lab Technician');
        $patient = $this->createPatient();
        $controller = app(ViralLoadController::class);
        $request = Request::create('/viral-load/store', 'POST', [
            'patient_id' => $patient->patient_id,
            'result_cpml' => 320,
            'result_date' => '2026-08-12',
            'sample_date' => '2026-08-11',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app()->call([$controller, 'store'], ['request' => $request]);

        $this->assertStringEndsWith('/viral-load', $response->getTargetUrl());

        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_manual_viral_load_sms_button_route_sends_message(): void
    {
        $user = $this->createUser('Clinician');
        $patient = $this->createPatient();
        $controller = app(ManualSmsController::class);
        $viralLoad = ViralLoad::query()->create([
            'patient_id' => $patient->patient_id,
            'result_cpml' => 250,
            'result_date' => '2026-08-12',
            'sample_date' => '2026-08-10',
        ]);
        $request = Request::create('/sms/viral-load/' . $viralLoad->vl_id . '/send-result', 'POST', [
            'phone' => '+211900000001',
            'message' => 'Custom viral load follow-up message',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app()->call([$controller, 'sendViralLoadResultMessage'], [
            'request' => $request,
            'viralLoad' => $viralLoad,
        ]);

        $this->assertTrue($response->isRedirect());

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'category' => 'viral_load_result_suppressed',
            'recipient' => '+211900000001',
            'status' => 'sent',
            'provider' => 'log',
            'message' => 'Custom viral load follow-up message',
        ]);
    }

    public function test_creating_appointment_does_not_auto_send_sms(): void
    {
        $user = $this->createUser('Administrator');
        $patient = $this->createPatient();
        $controller = app(AppointmentController::class);
        $request = Request::create('/appointments/store', 'POST', [
            'patient_id' => $patient->patient_id,
            'appointment_date' => '2026-08-14',
            'reason' => 'VL follow-up',
            'status' => 'Pending',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app()->call([$controller, 'store'], ['request' => $request]);

        $this->assertStringEndsWith('/appointments', $response->getTargetUrl());

        $this->assertDatabaseCount('notification_logs', 0);
    }

    public function test_manual_appointment_sms_button_route_sends_message(): void
    {
        $user = $this->createUser('Clinician');
        $controller = app(ManualSmsController::class);
        $appointment = Appointment::query()->create([
            'patient_id' => $this->createPatient()->patient_id,
            'appointment_date' => '2026-08-14',
            'reason' => 'EAC review',
            'status' => 'Pending',
        ]);
        $request = Request::create('/sms/appointments/' . $appointment->appointment_id . '/send', 'POST', [
            'phone' => '+211900000002',
            'message' => 'Custom appointment reminder message',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app()->call([$controller, 'sendAppointmentReminder'], [
            'request' => $request,
            'appointment' => $appointment,
        ]);

        $this->assertTrue($response->isRedirect());

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'sms',
            'category' => 'appointment_reminder',
            'recipient' => '+211900000002',
            'status' => 'sent',
            'provider' => 'log',
            'message' => 'Custom appointment reminder message',
        ]);
    }

    private function createPatient(): Patient
    {
        return Patient::query()->create([
            'art_number' => 'EE-TR-0001',
            'first_name' => 'Johnson',
            'last_name' => 'Tony',
            'sex' => 'Male',
            'phone' => '+211923546133',
        ]);
    }

    private function createUser(string $role): User
    {
        return User::query()->create([
            'name' => 'SMS Tester',
            'username' => strtolower(str_replace(' ', '_', $role)) . '_user',
            'role' => $role,
            'email' => strtolower(str_replace(' ', '.', $role)) . '@example.com',
            'password' => bcrypt('password'),
            'must_change_password' => false,
        ]);
    }
}
