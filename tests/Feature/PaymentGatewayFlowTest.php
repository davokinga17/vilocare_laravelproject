<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentGatewayFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_retired_payment_methods_cannot_be_used_for_new_payments(): void
    {
        config()->set('vilocare.payments.simulate_gateways', true);

        $user = $this->createUser();
        $patient = $this->createPatient();
        $controller = app(PaymentController::class);

        foreach (['pesapal', 'mastercard', 'manual', 'bank'] as $retiredMethod) {
            $request = Request::create('/payments', 'POST', [
                'patient_id' => $patient->patient_id,
                'payment_type' => 'eac_consultation',
                'service_label' => 'EAC Consultation Fee',
                'amount' => '25.00',
                'currency' => 'SSP',
                'payment_method' => $retiredMethod,
                'status' => 'paid',
            ]);
            $request->setUserResolver(fn () => $user);

            try {
                app()->call([$controller, 'store'], ['request' => $request]);
                $this->fail("{$retiredMethod} should not be accepted as a payment method.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('payment_method', $exception->errors());
            }
        }

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_gateway_simulation_can_mark_payment_as_paid(): void
    {
        config()->set('vilocare.payments.simulate_gateways', true);

        $user = $this->createUser();
        $controller = app(PaymentController::class);
        $payment = Payment::create([
            'patient_id' => $this->createPatient()->patient_id,
            'created_by' => $user->id,
            'payment_type' => 'eac_consultation',
            'service_label' => 'EAC Consultation Fee',
            'amount' => '25.00',
            'currency' => 'SSP',
            'payment_method' => 'mtn_momo',
            'status' => 'pending',
            'receipt_number' => 'VCR-TEST-0001',
            'meta' => [
                'mtn_momo' => [
                    'simulation' => true,
                ],
            ],
        ]);
        $request = Request::create('/payments/' . $payment->payment_id . '/simulate/mtn_momo', 'POST', [
            'status' => 'paid',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = app()->call([$controller, 'completeSimulation'], [
            'request' => $request,
            'payment' => $payment,
            'gateway' => 'mtn_momo',
        ]);

        $this->assertStringEndsWith('/payments/' . $payment->payment_id, $response->getTargetUrl());

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('paid', data_get($payment->meta, 'mtn_momo.result'));
    }

    public function test_payment_method_options_only_expose_cash_and_mtn_momo(): void
    {
        $controller = app(PaymentController::class);
        $method = new \ReflectionMethod($controller, 'paymentMethodOptions');

        $this->assertSame([
            'cash' => 'Cash',
            'mtn_momo' => 'MTN MoMo Pay',
        ], $method->invoke($controller));
    }

    private function createUser(): User
    {
        return User::query()->create([
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'role' => 'Administrator',
            'email' => '[email protected]',
            'password' => bcrypt('password'),
            'must_change_password' => false,
        ]);
    }

    private function createPatient(): Patient
    {
        return Patient::query()->create([
            'art_number' => 'ART-001',
            'first_name' => 'Amina',
            'last_name' => 'Peter',
            'sex' => 'Female',
            'phone' => '+211900000001',
        ]);
    }
}
