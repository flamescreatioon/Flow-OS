<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the dashboard summary aggregate array computed by
 * DashboardSummaryService. Money fields are exact decimal strings.
 */
class DashboardSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;

        return [
            'invoiced_total' => $data['invoiced_total'],
            'issued_invoices' => $data['issued_invoices'],
            'purchases_total' => $data['purchases_total'],
            'customers' => $data['customers'],
            'suppliers' => $data['suppliers'],
            'products' => $data['products'],
            'stock_units' => $data['stock_units'],
            'products_in_stock' => $data['products_in_stock'],
            'invoices_by_status' => $data['invoices_by_status'],
            'revenue_by_month' => array_map(
                fn (array $point) => [
                    'month' => $point['month'],
                    'total' => $point['total'],
                ],
                $data['revenue_by_month']
            ),
        ];
    }
}
