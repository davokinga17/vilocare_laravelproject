<?php

namespace Tests\Feature;

use App\Models\EACSession;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Models\ViralLoad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceptionistPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private int $patientSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        url()->forceRootUrl('http://localhost');
    }

    public function test_receptionist_can_only_access_front_desk_modules(): void
    {
        $receptionist = $this->user('Receptionist', 'reception');

        $this->actingAs($receptionist)->get('/payments')->assertOk();
        $this->actingAs($receptionist)->get('/appointments')->assertOk();
        $this->actingAs($receptionist)->get('/appointments/create')->assertForbidden();
        $this->actingAs($receptionist)->get('/dashboard')->assertForbidden();
        $this->actingAs($receptionist)->get('/patients')->assertForbidden();
        $this->actingAs($receptionist)->get('/reports')->assertForbidden();
    }

    public function test_clinician_request_is_pending_until_receptionist_accepts_cash(): void
    {
        $clinician = $this->user('Clinician', 'clinician');
        $receptionist = $this->user('Receptionist', 'reception');
        $patient = $this->patient();
        $session = EACSession::create([
            'patient_id' => $patient->patient_id,
            'session_number' => 1,
            'session_date' => now()->toDateString(),
            'completion_status' => 'Pending',
        ]);

        $this->actingAs($clinician)->post('/payments/request', [
            'patient_id' => $patient->patient_id,
            'eac_id' => $session->eac_id,
            'payment_type' => 'eac_consultation',
        ])->assertSessionHas('success');

        $payment = Payment::firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame($clinician->id, $payment->created_by);

        $this->actingAs($receptionist)
            ->post('/payments/' . $payment->payment_id . '/accept-cash')
            ->assertRedirect(route('payments.receipt', $payment));

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('cash', $payment->payment_method);
        $this->assertSame($receptionist->id, $payment->accepted_by);
        $this->assertNotNull($payment->accepted_at);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_eac_service_remains_locked_until_payment_is_accepted(): void
    {
        $clinician = $this->user('Clinician', 'clinician');
        $receptionist = $this->user('Receptionist', 'reception');
        $patient = $this->patient();
        $session = EACSession::create([
            'patient_id' => $patient->patient_id,
            'session_number' => 1,
            'session_date' => now()->toDateString(),
            'completion_status' => 'Pending',
        ]);

        $this->actingAs($clinician)->post('/eac/' . $session->eac_id . '/complete')->assertSessionHas('warning');
        $this->assertSame('Pending', $session->fresh()->completion_status);

        $this->actingAs($clinician)->post('/payments/request', [
            'patient_id' => $patient->patient_id,
            'eac_id' => $session->eac_id,
            'payment_type' => 'eac_consultation',
        ]);
        $payment = Payment::firstOrFail();
        $this->actingAs($receptionist)->post('/payments/' . $payment->payment_id . '/accept-cash');

        $this->actingAs($clinician)->post('/eac/' . $session->eac_id . '/complete')->assertSessionHas('success');
        $this->assertSame('Completed', $session->fresh()->completion_status);
    }

    public function test_result_printing_requires_payment_for_the_specific_result(): void
    {
        $clinician = $this->user('Clinician', 'clinician');
        $receptionist = $this->user('Receptionist', 'reception');
        $patient = $this->patient();
        $result = ViralLoad::create([
            'patient_id' => $patient->patient_id,
            'sample_date' => now()->toDateString(),
            'result_date' => now()->toDateString(),
            'result_cpml' => 120,
            'status' => 'Suppressed',
        ]);

        $this->actingAs($clinician)->get('/patients/' . $patient->patient_id . '/result/print')->assertRedirect();

        $this->actingAs($clinician)->post('/payments/request', [
            'patient_id' => $patient->patient_id,
            'vl_id' => $result->vl_id,
            'payment_type' => 'result_print',
        ]);
        $payment = Payment::firstOrFail();
        $this->actingAs($receptionist)->post('/payments/' . $payment->payment_id . '/accept-cash');

        $this->actingAs($clinician)->get('/patients/' . $patient->patient_id . '/result/print')->assertOk();
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => $role . ' User',
            'username' => $username,
            'email' => $username . '@example.test',
            'role' => $role,
            'password' => 'password',
            'must_change_password' => false,
        ]);
    }

    private function patient(): Patient
    {
        return Patient::create([
            'art_number' => 'ART-TEST-' . (++$this->patientSequence),
            'first_name' => 'Grace',
            'last_name' => 'Peter',
            'sex' => 'Female',
            'phone' => '+211900000001',
        ]);
    }
}
