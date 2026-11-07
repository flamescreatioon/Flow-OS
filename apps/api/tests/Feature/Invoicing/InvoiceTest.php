<?php

namespace Tests\Feature\Invoicing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Product;
use App\Models\TaxSetting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function invoiceContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(?string $customerId = null, string $quantity = '2.000', string $price = '1500.0000', ?string $discount = null): array
    {
        $line = [
            'description' => 'Milo 500g carton',
            'quantity' => $quantity,
            'unit_price' => $price,
        ];

        if ($discount !== null) {
            $line['discount'] = $discount;
        }

        return array_filter([
            'customer_id' => $customerId,
            'lines' => [$line],
        ], fn ($value) => $value !== null);
    }

    public function test_a_draft_is_created_without_a_number_and_with_no_tax(): void
    {
        [$organization] = $this->invoiceContext();

        $response = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated();

        $response->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.document_number', null)
            ->assertJsonPath('data.invoice_number', null)
            ->assertJsonPath('data.tax_total', '0.0000')
            ->assertJsonPath('data.grand_total', '3000.0000')
            ->assertJsonPath('data.lines.0.line_total', '3000.0000')
            ->assertJsonPath('data.lines.0.tax_amount', '0.0000')
            ->assertJsonPath('data.currency', 'NGN');

        $this->assertDatabaseHas('invoices', [
            'organization_id' => $organization->id,
            'status' => 'draft',
            'subtotal' => 30000000,
            'grand_total' => 30000000,
        ]);
    }

    public function test_issue_snapshots_tax_and_assigns_the_sequential_number(): void
    {
        [$organization] = $this->invoiceContext();
        TaxSetting::factory()->vatExclusive()->for($organization)->create();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload(quantity: '2.000', price: '1500.0000')
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertOk()
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.document_number', 'INV-000001')
            ->assertJsonPath('data.invoice_number', 1)
            ->assertJsonPath('data.tax_rate_percent', '7.5000')
            ->assertJsonPath('data.tax_mode', 'exclusive')
            ->assertJsonPath('data.subtotal', '3000.0000')
            ->assertJsonPath('data.tax_total', '225.0000')
            ->assertJsonPath('data.grand_total', '3225.0000');

        // The second invoice takes the next number in the sequence.
        $secondId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$secondId}/issue")
            ->assertOk()
            ->assertJsonPath('data.document_number', 'INV-000002');
    }

    public function test_issue_freezes_the_snapshot_against_later_changes(): void
    {
        [$organization] = $this->invoiceContext();
        TaxSetting::factory()->vatExclusive()->for($organization)->create();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertOk()
            ->assertJsonPath('data.tax_rate_percent', '7.5000');

        // The organization's tax rate changes after issue; the issued
        // document must not move.
        TaxSetting::query()
            ->where('organization_id', $organization->id)
            ->update(['tax_rate_percent' => 100000]);

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.tax_rate_percent', '7.5000')
            ->assertJsonPath('data.grand_total', '3225.0000');
    }

    public function test_an_issued_invoice_cannot_be_edited_or_issued_again(): void
    {
        [$organization] = $this->invoiceContext();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")->assertOk();

        $this->patchJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}", [
            'note' => 'amended',
        ])->assertUnprocessable();

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertUnprocessable();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'issued',
            'grand_total' => 30000000,
        ]);
    }

    public function test_an_invoice_needs_a_line_to_be_issued(): void
    {
        [$organization] = $this->invoiceContext();

        // A draft cannot exist without lines through the API, so the guard
        // is exercised by deleting the lines under an existing draft.
        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->applyDeleteLines($invoiceId);

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertUnprocessable();
    }

    private function applyDeleteLines(string $invoiceId): void
    {
        // Test-only helper: removes the lines of a draft directly, because
        // the API always keeps at least one line.
        Invoice::query()->findOrFail($invoiceId)->lines()->delete();
    }

    public function test_void_is_terminal_and_retains_the_record(): void
    {
        [$organization] = $this->invoiceContext();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/void")
            ->assertOk()
            ->assertJsonPath('data.status', 'voided');

        // There is no delete and no un-void.
        $this->deleteJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}")
            ->assertMethodNotAllowed();

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/void")
            ->assertUnprocessable();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'voided',
        ]);
    }

    public function test_a_draft_can_be_issued_after_voiding_a_different_draft(): void
    {
        [$organization] = $this->invoiceContext();

        $voidedId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$voidedId}/void")->assertOk();

        // A voided draft never consumed a number, so the next issue is
        // INV-000001.
        $issuedId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$issuedId}/issue")
            ->assertOk()
            ->assertJsonPath('data.document_number', 'INV-000001');
    }

    public function test_a_line_against_a_product_from_another_organization_is_rejected(): void
    {
        [$organization] = $this->invoiceContext();
        $foreignProduct = Product::factory()->create();

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices", [
            'lines' => [[
                'product_id' => $foreignProduct->id,
                'description' => 'Foreign goods',
                'quantity' => '1.000',
                'unit_price' => '100.0000',
            ]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_an_invoice_replays_idempotently_on_issue(): void
    {
        [$organization] = $this->invoiceContext();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertCreated()->json('data.id');

        $headers = ['Idempotency-Key' => 'issue-key-1'];

        $first = $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue", [], $headers)
            ->assertOk();
        $second = $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue", [], $headers)
            ->assertOk();

        $this->assertSame($first->json('data.document_number'), $second->json('data.document_number'));
        $this->assertSame('INV-000001', $second->json('data.document_number'));
        $this->assertDatabaseHas('document_sequences', [
            'organization_id' => $organization->id,
            'document_type' => 'invoice',
            'next_value' => 2,
        ]);
    }

    public function test_an_invoice_from_another_organization_is_not_visible(): void
    {
        [$organization] = $this->invoiceContext();

        $foreign = Invoice::factory()->for(Organization::factory())->issued()->create();

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_a_member_can_view_but_not_write_invoices(): void
    {
        [$organization, $admin] = $this->invoiceContext();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices")->assertOk();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_an_unverified_email_cannot_create_an_invoice(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload()
        )->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_an_archived_customer_cannot_be_invoiced(): void
    {
        [$organization] = $this->invoiceContext();
        $archived = Customer::factory()->for($organization)->archived()->create();

        $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload($archived->id)
        )->assertUnprocessable();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_the_customer_snapshot_is_taken_at_creation(): void
    {
        [$organization] = $this->invoiceContext();
        $customer = Customer::factory()->for($organization)->create(['name' => 'Snapshot Traders']);

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload($customer->id)
        )->assertCreated()->json('data.id');

        // Renaming the customer later does not rewrite the invoice.
        $customer->forceFill(['name' => 'Renamed Traders'])->save();

        $this->getJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Snapshot Traders');
    }

    public function test_an_inclusive_tax_invoice_extracts_the_tax(): void
    {
        [$organization] = $this->invoiceContext();
        TaxSetting::factory()->vatInclusive()->for($organization)->create();

        $invoiceId = $this->postJson(
            "/api/v1/organizations/{$organization->id}/invoices",
            $this->invoicePayload(quantity: '1.000', price: '1075.0000')
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertOk()
            ->assertJsonPath('data.tax_mode', 'inclusive')
            ->assertJsonPath('data.tax_total', '75.0000')
            ->assertJsonPath('data.subtotal', '1000.0000')
            ->assertJsonPath('data.grand_total', '1075.0000');
    }

    public function test_issuing_twice_across_the_unit_boundary_stays_exact(): void
    {
        [$organization] = $this->invoiceContext();
        $unit = UnitOfMeasure::factory()->for($organization)->withPrecision(3)->create();
        $product = Product::factory()->for($organization)->withUnit($unit)->stocked()->create();

        $invoiceId = $this->postJson("/api/v1/organizations/{$organization->id}/invoices", [
            'lines' => [[
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => '3.333',
                'unit_price' => '1500.2500',
            ]],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/organizations/{$organization->id}/invoices/{$invoiceId}/issue")
            ->assertOk()
            ->assertJsonPath('data.lines.0.line_total', '5000.3333');
    }
}
