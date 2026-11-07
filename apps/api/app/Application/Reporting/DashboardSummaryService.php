<?php

namespace App\Application\Reporting;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Supplier;

/**
 * The organization dashboard summary (ADR: reporting-domain.md).
 *
 * A derived, presentation-oriented read model. Every number here is an
 * aggregate over the owning domains' authoritative records and nothing in
 * this service writes; it can be recomputed at any time.
 */
final class DashboardSummaryService
{
    private const MONTHS_OF_TREND = 6;

    /**
     * @return array<string, mixed>
     */
    public function summary(Organization $organization): array
    {
        $issued = Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'issued');

        $invoicedTotal = (int) $issued->clone()->sum('grand_total');
        $issuedCount = $issued->clone()->count();

        $purchasesTotal = (int) GoodsReceipt::query()
            ->where('organization_id', $organization->id)
            ->sum('total_cost');

        $expensesTotal = (int) Expense::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'recorded')
            ->sum('amount');
        $expensesCount = Expense::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'recorded')
            ->count();

        $customers = Customer::query()
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at')
            ->count();

        $suppliers = Supplier::query()
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at')
            ->count();

        $products = Product::query()
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at')
            ->count();

        $stockUnits = (int) StockBalance::query()
            ->where('organization_id', $organization->id)
            ->sum('quantity');

        $productsInStock = StockBalance::query()
            ->where('organization_id', $organization->id)
            ->where('quantity', '!=', 0)
            ->distinct('product_id')
            ->count('product_id');

        $statusCounts = ['draft' => 0, 'issued' => 0, 'voided' => 0];
        $byStatus = Invoice::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->get();
        foreach ($byStatus as $row) {
            // The model casts status to the enum; keyed aggregation uses the
            // backing value.
            $status = $row->status;
            $key = $status instanceof InvoiceStatus ? $status->value : (string) $status;
            $statusCounts[$key] = (int) $row->aggregate;
        }

        return [
            'invoiced_total' => UnitPrice::of($invoicedTotal)->toString(),
            'issued_invoices' => $issuedCount,
            'purchases_total' => UnitPrice::of($purchasesTotal)->toString(),
            'expenses_total' => UnitPrice::of($expensesTotal)->toString(),
            'expenses' => $expensesCount,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'products' => $products,
            'stock_units' => Quantity::of($stockUnits)->toString(),
            'products_in_stock' => $productsInStock,
            'invoices_by_status' => $statusCounts,
            'revenue_by_month' => $this->revenueByMonth($organization->id),
        ];
    }

    /**
     * The last six calendar months, always present, bucketed in PHP from the
     * issued rows so the query stays portable across database drivers.
     *
     * @return array<int, array{month: string, total: string}>
     */
    private function revenueByMonth(string $organizationId): array
    {
        $since = now()->subMonths(self::MONTHS_OF_TREND - 1)->startOfMonth();

        $buckets = [];
        for ($offset = self::MONTHS_OF_TREND - 1; $offset >= 0; $offset--) {
            $buckets[now()->subMonths($offset)->format('Y-m')] = 0;
        }

        Invoice::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'issued')
            ->where('issued_at', '>=', $since)
            ->orderBy('issued_at')
            ->get(['issued_at', 'grand_total'])
            ->each(function (Invoice $invoice) use (&$buckets): void {
                $month = $invoice->issued_at?->format('Y-m');

                if ($month !== null && array_key_exists($month, $buckets)) {
                    $buckets[$month] += $invoice->grand_total;
                }
            });

        return array_map(
            fn (string $month, int $total) => [
                'month' => $month,
                'total' => UnitPrice::of($total)->toString(),
            ],
            array_keys($buckets),
            $buckets
        );
    }
}
