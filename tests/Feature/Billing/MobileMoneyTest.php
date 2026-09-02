<?php
namespace Tests\Feature\Billing;

use App\Models\Facility;
use App\Models\MobileMoneyTransaction;
use App\Models\Patient;
use App\Services\Payment\MobileMoneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileMoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_mtn_momo_payment_initiation(): void
    {
        Http::fake([
            '*/collection/v1_0/requesttopay' => Http::response(['status' => 'PENDING'], 202),
            '*/collection/token/'             => Http::response(['access_token' => 'mock-token', 'token_type' => 'Bearer', 'expires_in' => 3600], 200),
        ]);

        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $service = new MobileMoneyService();
        $txn     = $service->initiatePayment(
            provider:    'mtn_momo',
            patientId:   $patient->id,
            facilityId:  $facility->id,
            amountXaf:   25000,
            phoneNumber: '+237670000001',
            reference:   'BILL-2026-001',
            description: 'Consultation fee',
        );

        $this->assertInstanceOf(MobileMoneyTransaction::class, $txn);
        $this->assertEquals('pending', $txn->status);
        $this->assertEquals('mtn_momo', $txn->provider);
    }

    public function test_mobile_money_transaction_stored(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $txn = MobileMoneyTransaction::create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'provider'     => 'orange_money',
            'amount_xaf'   => 15000,
            'phone_number' => '+237695000001',
            'reference'    => 'BILL-2026-002',
            'status'       => 'pending',
            'provider_ref' => 'OM-TXN-12345',
        ]);

        $this->assertEquals('orange_money', $txn->provider);
        $this->assertEquals(15000, $txn->amount_xaf);
    }
}
