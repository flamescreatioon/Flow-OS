<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'branch_id' => null,
            'customer_id' => null,
            'customer_name' => null,
            'invoice_number' => null,
            'document_number' => null,
            'status' => 'draft',
            'issued_at' => null,
            'voided_at' => null,
            'due_on' => null,
            'currency' => 'NGN',
            'tax_rate_percent' => null,
            'tax_mode' => null,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'note' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => 'issued',
            'invoice_number' => 1,
            'document_number' => 'INV-000001',
            'issued_at' => now(),
            'subtotal' => 10000000,
            'tax_total' => 750000,
            'grand_total' => 10750000,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (): array => [
            'status' => 'voided',
            'invoice_number' => 1,
            'document_number' => 'INV-000001',
            'issued_at' => now()->subDay(),
            'voided_at' => now(),
        ]);
    }
}
