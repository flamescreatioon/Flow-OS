<?php

namespace Tests\Feature\Invoicing;

use App\Application\Notifications\ChannelResolver;
use App\Jobs\DeliverInvoiceNotification;
use App\Models\Customer;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class InvoiceDeliveryTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function deliveryContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    /**
     * Create and issue an invoice, optionally against a customer.
     */
    private function issuedInvoice(Organization $organization, ?Customer $customer = null): string
    {
        $payload = [
            'lines' => [[
                'description' => 'Monthly service',
                'quantity' => '1.000',
                'unit_price' => '1000.0000',
            ]],
        ];

        if ($customer) {
            $payload['customer_id'] = $customer->id;
        }

        $invoiceId = $this->postJson("/api/v1/organizations/{$organization->id}/invoices", $payload)
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertOk();

        return $invoiceId;
    }

    public function test_only_an_issued_invoice_can_be_sent(): void
    {
        [$organization] = $this->deliveryContext();

        $draftId = $this->postJson("/api/v1/organizations/{$organization->id}/invoices", [
            'lines' => [['description' => 'X', 'quantity' => '1.000', 'unit_price' => '10.0000']],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$draftId}/send", [
            'channel' => 'email',
            'recipient' => 'reader@example.com',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('notification_deliveries', 0);
    }

    public function test_an_email_send_queues_a_delivery_and_the_job(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
            'recipient' => 'accounts@customer.example',
        ])->assertStatus(202)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.driver', 'mail')
            ->assertJsonPath('data.recipient', 'accounts@customer.example');

        $this->assertDatabaseHas('notification_deliveries', [
            'entity_type' => 'invoice',
            'entity_id' => $invoiceId,
            'channel' => 'email',
            'driver' => 'mail',
            'status' => 'queued',
        ]);

        Bus::assertDispatched(DeliverInvoiceNotification::class);
    }

    public function test_the_recipient_defaults_from_the_customer(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $customer = Customer::factory()->for($organization)->create([
            'email' => 'billing@customer.example',
            'phone' => '0803 555 0000',
        ]);
        $invoiceId = $this->issuedInvoice($organization, $customer);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
        ])->assertStatus(202)
            ->assertJsonPath('data.recipient', 'billing@customer.example');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'whatsapp',
        ])->assertStatus(202)
            ->assertJsonPath('data.recipient', '0803 555 0000')
            ->assertJsonPath('data.driver', 'log');
    }

    public function test_a_missing_recipient_is_rejected(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'sms',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('notification_deliveries', 0);
    }

    public function test_an_invalid_email_recipient_is_rejected(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
            'recipient' => 'not-an-email',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('notification_deliveries', 0);
    }

    public function test_an_issued_invoice_send_replays_idempotently(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);
        $headers = ['Idempotency-Key' => 'send-key-1'];
        $payload = ['channel' => 'email', 'recipient' => 'accounts@customer.example'];

        $first = $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", $payload, $headers)
            ->assertStatus(202);
        $second = $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", $payload, $headers)
            ->assertStatus(202);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('notification_deliveries', 1);
    }

    public function test_the_mail_driver_delivers_and_marks_the_delivery_sent(): void
    {
        Bus::fake();
        Mail::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        // The request is wrapped in the test's transaction, so an
        // after-commit dispatch is captured rather than run; the job is
        // driven directly to prove its behavior.
        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
            'recipient' => 'accounts@customer.example',
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'queued');

        Bus::assertDispatched(DeliverInvoiceNotification::class);

        $job = Bus::dispatched(DeliverInvoiceNotification::class)[0];
        $job->handle(app(ChannelResolver::class));

        Mail::assertSentCount(1);

        $this->assertDatabaseHas('notification_deliveries', [
            'entity_id' => $invoiceId,
            'channel' => 'email',
            'driver' => 'mail',
            'status' => 'sent',
        ]);
    }

    public function test_deliveries_are_listed_for_the_invoice(): void
    {
        Bus::fake();
        Mail::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
            'recipient' => 'accounts@customer.example',
        ])->assertStatus(202);

        $job = Bus::dispatched(DeliverInvoiceNotification::class)[0];
        $job->handle(app(ChannelResolver::class));

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/deliveries")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'sent');
    }

    public function test_deliveries_of_another_invoice_are_not_listed(): void
    {
        Mail::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        NotificationDelivery::factory()->for($organization, 'organization')->create([
            'entity_type' => 'invoice',
            'entity_id' => 'some-other-invoice',
        ]);

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/deliveries")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_member_can_read_but_not_send(): void
    {
        Bus::fake();
        [$organization] = $this->deliveryContext();
        $invoiceId = $this->issuedInvoice($organization);

        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/deliveries")->assertOk();

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/send", [
            'channel' => 'email',
            'recipient' => 'accounts@customer.example',
        ])->assertForbidden();

        $this->assertDatabaseCount('notification_deliveries', 0);
    }
}
