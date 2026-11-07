<?php

namespace Tests\Feature\Reporting;

use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryLocation;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\WithRbac;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use WithRbac;

    /**
     * @return array{0: Organization, 1: User}
     */
    protected function summaryContext(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($organization, $user);
        Sanctum::actingAs($user);

        return [$organization, $user];
    }

    public function test_the_summary_aggregates_the_workspace_numbers(): void
    {
        [$organization] = $this->summaryContext();

        // Two issued invoices in different months: the "amount made" and the
        // monthly trend both come from these.
        Invoice::factory()->for($organization)->issued()->create([
            'grand_total' => 32250000,
            'issued_at' => now()->startOfMonth()->addHours(3),
        ]);
        Invoice::factory()->for($organization)->issued()->create([
            'invoice_number' => 2,
            'document_number' => 'INV-000002',
            'grand_total' => 10750000,
            'issued_at' => now()->subMonths(2)->startOfMonth()->addHours(3),
        ]);

        // A draft and a voided invoice are excluded from the totals but
        // appear in the status breakdown.
        Invoice::factory()->for($organization)->create(['status' => 'draft']);
        Invoice::factory()->for($organization)->voided()->create(['invoice_number' => 3, 'document_number' => 'INV-000003']);

        $receipt = GoodsReceipt::factory()->for($organization)->create(['total_cost' => 125025000]);
        GoodsReceiptLine::factory()->for($receipt)->create(['organization_id' => $organization->id]);

        Customer::factory()->for($organization)->create();
        Supplier::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->stocked()->create();
        $location = InventoryLocation::factory()->for($organization)->create();
        StockBalance::factory()->for($organization)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 5000,
        ]);

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/dashboard-summary")
            ->assertOk();

        $response->assertJsonPath('data.invoiced_total', '4300.0000')
            ->assertJsonPath('data.issued_invoices', 2)
            ->assertJsonPath('data.purchases_total', '12502.5000')
            ->assertJsonPath('data.customers', 1)
            ->assertJsonPath('data.suppliers', 1)
            ->assertJsonPath('data.products', 1)
            ->assertJsonPath('data.stock_units', '5.000')
            ->assertJsonPath('data.products_in_stock', 1)
            ->assertJsonPath('data.invoices_by_status.draft', 1)
            ->assertJsonPath('data.invoices_by_status.issued', 2)
            ->assertJsonPath('data.invoices_by_status.voided', 1);

        $months = $response->json('data.revenue_by_month');
        $this->assertCount(6, $months);
        $this->assertSame(now()->subMonths(5)->format('Y-m'), $months[0]['month']);
        $this->assertSame('0.0000', $months[0]['total']);
        $this->assertSame(now()->subMonths(2)->format('Y-m'), $months[3]['month']);
        $this->assertSame('1075.0000', $months[3]['total']);
        $this->assertSame(now()->format('Y-m'), $months[5]['month']);
        $this->assertSame('3225.0000', $months[5]['total']);
    }

    public function test_the_summary_reports_zeros_for_an_empty_workspace(): void
    {
        [$organization] = $this->summaryContext();

        $response = $this->getJson("/api/v1/organizations/{$organization->id}/dashboard-summary")
            ->assertOk()
            ->assertJsonPath('data.invoiced_total', '0.0000')
            ->assertJsonPath('data.issued_invoices', 0)
            ->assertJsonPath('data.purchases_total', '0.0000')
            ->assertJsonPath('data.customers', 0)
            ->assertJsonPath('data.stock_units', '0.000')
            ->assertJsonPath('data.products_in_stock', 0)
            ->assertJsonPath('data.invoices_by_status.draft', 0);

        $months = $response->json('data.revenue_by_month');
        $this->assertCount(6, $months);
        foreach ($months as $month) {
            $this->assertSame('0.0000', $month['total']);
        }
    }

    public function test_the_summary_never_leaks_another_organization(): void
    {
        [$organization] = $this->summaryContext();

        $other = Organization::factory()->create();
        Invoice::factory()->for($other)->issued()->create(['grand_total' => 99900000]);
        Customer::factory()->for($other)->create();
        $receipt = GoodsReceipt::factory()->for($other)->create(['total_cost' => 50000000]);
        GoodsReceiptLine::factory()->for($receipt)->create(['organization_id' => $organization->id]);

        $this->getJson("/api/v1/organizations/{$organization->id}/dashboard-summary")
            ->assertOk()
            ->assertJsonPath('data.invoiced_total', '0.0000')
            ->assertJsonPath('data.customers', 0)
            ->assertJsonPath('data.purchases_total', '0.0000');
    }

    public function test_a_member_can_read_the_summary_but_an_outsider_cannot(): void
    {
        [$organization] = $this->summaryContext();

        $member = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationMember($organization, $member);
        Sanctum::actingAs($member);
        $this->getJson("/api/v1/organizations/{$organization->id}/dashboard-summary")->assertOk();

        $outsider = Organization::factory()->create();
        $outsiderUser = User::factory()->create(['email_verified_at' => now()]);
        $this->createOrganizationAdmin($outsider, $outsiderUser);
        Sanctum::actingAs($outsiderUser);
        $this->getJson("/api/v1/organizations/{$organization->id}/dashboard-summary")->assertForbidden();
    }
}
