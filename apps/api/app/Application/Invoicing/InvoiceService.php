<?php

namespace App\Application\Invoicing;

use App\Application\Audit\AuditLogger;
use App\Application\Documents\DocumentNumberService;
use App\Application\Notifications\ChannelResolver;
use App\Application\Notifications\NotificationMessage;
use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Invoicing\Services\InvoiceTotalsCalculator;
use App\Domain\Shared\Services\DecimalMath;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Domain\Shared\ValueObjects\ScaledDecimal;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationChannel;
use App\Enums\TaxMode;
use App\Jobs\DeliverInvoiceNotification;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\Product;
use App\Models\TaxSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The invoice lifecycle (ADR 0036): draft -> issued -> voided.
 *
 * A draft is freely editable and carries no number. Issue snapshots the
 * customer, tax, and every line's commercial values, assigns the sequential
 * document number, and freezes the document. Void is terminal. Invoices have
 * no stock effect and no payment state in this slice.
 */
final class InvoiceService
{
    public function createDraft(Organization $organization, array $data, User $actor): Invoice
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $customer = null;

            if (! empty($data['customer_id'])) {
                $customer = $this->resolveCustomer($organization, $data['customer_id']);
            }

            $branch = null;

            if (! empty($data['branch_id'])) {
                $branch = $this->resolveBranch($organization, $data['branch_id']);
            }

            $lines = $this->prepareLines($organization, $data['lines']);

            $invoice = Invoice::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch?->id,
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name,
                'status' => InvoiceStatus::Draft->value,
                'due_on' => $data['due_on'] ?? null,
                'currency' => $organization->currency,
                'note' => $data['note'] ?? null,
                'subtotal' => $lines['subtotal']->value,
                'discount_total' => $lines['discount_total']->value,
                'tax_total' => 0,
                'grand_total' => $lines['subtotal']->value,
            ]);

            foreach ($lines['lines'] as $line) {
                InvoiceLine::create([
                    'organization_id' => $organization->id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $line['product']?->id,
                    'description' => $line['description'],
                    'quantity' => $line['quantity']->scaledValue(),
                    'unit_price' => $line['unit_price']->scaledValue(),
                    'discount' => $line['discount']->scaledValue(),
                    'tax_amount' => 0,
                    'line_total' => $line['line_total']->value,
                ]);
            }

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'invoicing.invoice_created',
                'invoice',
                $invoice->id,
                [
                    'organization_id' => $organization->id,
                    'customer_id' => $customer?->id,
                    'line_count' => count($lines['lines']),
                    'grand_total' => $lines['subtotal']->toString(),
                ]
            );

            return $invoice->load(['lines', 'customer', 'branch']);
        });
    }

    public function updateDraft(Organization $organization, Invoice $invoice, array $data, User $actor): Invoice
    {
        if ($invoice->organization_id !== $organization->id) {
            throw new LogicException('The invoice does not belong to this organization.');
        }

        if (! $invoice->isDraft()) {
            throw new LogicException('Only a draft invoice can be edited.');
        }

        return DB::transaction(function () use ($organization, $invoice, $data, $actor) {
            // Re-read the row under a lock: the route-bound instance may be
            // stale if a concurrent request changed the state between the
            // check above and this transaction.
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->organization_id !== $organization->id) {
                throw new LogicException('The invoice does not belong to this organization.');
            }

            if (! $invoice->isDraft()) {
                throw new LogicException('Only a draft invoice can be edited.');
            }

            if (array_key_exists('customer_id', $data)) {
                if ($data['customer_id'] === null) {
                    $invoice->customer_id = null;
                    $invoice->customer_name = null;
                } else {
                    $customer = $this->resolveCustomer($organization, $data['customer_id']);
                    $invoice->customer_id = $customer->id;
                    $invoice->customer_name = $customer->name;
                }
            }

            if (array_key_exists('branch_id', $data)) {
                $invoice->branch_id = $data['branch_id'] === null
                    ? null
                    : $this->resolveBranch($organization, $data['branch_id'])->id;
            }

            if (array_key_exists('due_on', $data)) {
                $invoice->due_on = $data['due_on'];
            }

            if (array_key_exists('note', $data)) {
                $invoice->note = $data['note'];
            }

            if (array_key_exists('lines', $data)) {
                $lines = $this->prepareLines($organization, $data['lines']);

                // Lines are replaced as a whole; there is no partial line
                // edit, so the document always stays internally consistent.
                InvoiceLine::query()->where('invoice_id', $invoice->id)->delete();

                foreach ($lines['lines'] as $line) {
                    InvoiceLine::create([
                        'organization_id' => $organization->id,
                        'invoice_id' => $invoice->id,
                        'product_id' => $line['product']?->id,
                        'description' => $line['description'],
                        'quantity' => $line['quantity']->scaledValue(),
                        'unit_price' => $line['unit_price']->scaledValue(),
                        'discount' => $line['discount']->scaledValue(),
                        'tax_amount' => 0,
                        'line_total' => $line['line_total']->value,
                    ]);
                }

                $invoice->subtotal = $lines['subtotal']->value;
                $invoice->discount_total = $lines['discount_total']->value;
                $invoice->tax_total = 0;
                $invoice->grand_total = $lines['subtotal']->value;
            }

            $invoice->save();

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'invoicing.invoice_updated',
                'invoice',
                $invoice->id,
                ['organization_id' => $organization->id]
            );

            return $invoice->load(['lines', 'customer', 'branch']);
        });
    }

    public function issue(Organization $organization, Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->organization_id !== $organization->id) {
            throw new LogicException('The invoice does not belong to this organization.');
        }

        if (! $invoice->isDraft()) {
            throw new LogicException('Only a draft invoice can be issued.');
        }

        return DB::transaction(function () use ($organization, $invoice, $actor) {
            // Re-read the row under a lock so a concurrent edit or void that
            // raced the check above cannot be issued or double-issued.
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->organization_id !== $organization->id) {
                throw new LogicException('The invoice does not belong to this organization.');
            }

            if (! $invoice->isDraft()) {
                throw new LogicException('Only a draft invoice can be issued.');
            }

            $stored = $invoice->lines()->orderBy('created_at')->orderBy('id')->get()->all();

            if ($stored === []) {
                throw new LogicException('An invoice needs at least one line to be issued.');
            }

            // The tax snapshot comes from the organization's configuration at
            // issue time and is frozen onto the document.
            $settings = TaxSetting::query()->where('organization_id', $organization->id)->first();
            $rate = ($settings && $settings->tax_enabled && $settings->tax_rate_percent !== null)
                ? UnitPrice::of($settings->tax_rate_percent)
                : null;
            $mode = $settings?->tax_mode ?? TaxMode::Exclusive;

            $calculatorLines = array_map(fn (InvoiceLine $line) => [
                'quantity' => $line->quantity(),
                'unit_price' => $line->unitPrice(),
                'discount' => $line->discount(),
            ], $stored);

            $totals = InvoiceTotalsCalculator::calculate($calculatorLines, $rate, $mode);

            $number = app(DocumentNumberService::class)->allocateNext($organization, DocumentNumberService::INVOICE);

            $invoice->forceFill([
                'status' => InvoiceStatus::Issued->value,
                'issued_at' => now(),
                'invoice_number' => $number,
                'document_number' => app(DocumentNumberService::class)->render(DocumentNumberService::INVOICE, $number),
                'currency' => $organization->currency,
                'tax_rate_percent' => $rate?->scaledValue(),
                'tax_mode' => $rate === null ? null : $mode->value,
                'subtotal' => $totals['subtotal']->value,
                'discount_total' => $totals['discount_total']->value,
                'tax_total' => $totals['tax_total']->value,
                'grand_total' => $totals['grand_total']->value,
            ])->save();

            foreach ($stored as $index => $line) {
                $line->forceFill(['tax_amount' => $totals['lines'][$index]['tax_amount']->value])->save();
            }

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'invoicing.invoice_issued',
                'invoice',
                $invoice->id,
                [
                    'organization_id' => $organization->id,
                    'document_number' => $invoice->document_number,
                    'tax_total' => $totals['tax_total']->toString(),
                    'grand_total' => $totals['grand_total']->toString(),
                ]
            );

            return $invoice->load(['lines', 'customer', 'branch']);
        });
    }

    public function void(Organization $organization, Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->organization_id !== $organization->id) {
            throw new LogicException('The invoice does not belong to this organization.');
        }

        if ($invoice->isVoided()) {
            throw new LogicException('The invoice is already voided.');
        }

        return DB::transaction(function () use ($organization, $invoice, $actor) {
            // Re-read the row under a lock so two concurrent voids (or a void
            // racing an issue) cannot both apply.
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->organization_id !== $organization->id) {
                throw new LogicException('The invoice does not belong to this organization.');
            }

            if ($invoice->isVoided()) {
                throw new LogicException('The invoice is already voided.');
            }

            $invoice->forceFill([
                'status' => InvoiceStatus::Voided->value,
                'voided_at' => now(),
            ])->save();

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'invoicing.invoice_voided',
                'invoice',
                $invoice->id,
                ['organization_id' => $organization->id]
            );

            return $invoice->load(['lines', 'customer', 'branch']);
        });
    }

    /**
     * Record a delivery attempt and queue the transport call (ADR 0037).
     */
    public function send(Organization $organization, Invoice $invoice, array $data, User $actor): NotificationDelivery
    {
        if ($invoice->organization_id !== $organization->id) {
            throw new LogicException('The invoice does not belong to this organization.');
        }

        if (! $invoice->isIssued()) {
            throw new LogicException('Only an issued invoice can be sent.');
        }

        $channel = NotificationChannel::from($data['channel']);

        $recipient = trim($data['recipient'] ?? '');

        if ($recipient === '') {
            $recipient = $this->defaultRecipient($invoice, $channel);
        }

        if ($recipient === '') {
            throw new LogicException(
                "The invoice has no {$channel->value} recipient; provide one or attach a customer."
            );
        }

        if ($channel === NotificationChannel::Email && ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new LogicException('The recipient is not a valid email address.');
        }

        $resolver = app(ChannelResolver::class);
        $driver = $resolver->driverFor($channel);

        // The delivery row is created under a lock that re-checks the invoice
        // state, so a void racing the send cannot message a voided document.
        $delivery = DB::transaction(function () use ($organization, $invoice, $channel, $driver, $recipient) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->organization_id !== $organization->id) {
                throw new LogicException('The invoice does not belong to this organization.');
            }

            if (! $invoice->isIssued()) {
                throw new LogicException('Only an issued invoice can be sent.');
            }

            return NotificationDelivery::create([
                'organization_id' => $organization->id,
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'channel' => $channel,
                'driver' => $driver,
                'recipient' => $recipient,
                'status' => 'queued',
            ]);
        });

        $message = $this->buildMessage($organization, $invoice, $channel, $delivery->id);

        DeliverInvoiceNotification::dispatch(
            $delivery->id,
            $organization->id,
            $message->subject,
            $message->body
        )->afterCommit();

        return $delivery;
    }

    private function defaultRecipient(Invoice $invoice, NotificationChannel $channel): string
    {
        $customer = $invoice->customer;

        if (! $customer) {
            return '';
        }

        return match ($channel) {
            NotificationChannel::Email => trim((string) $customer->email),
            NotificationChannel::Sms, NotificationChannel::Whatsapp => trim((string) $customer->phone),
        };
    }

    private function buildMessage(
        Organization $organization,
        Invoice $invoice,
        NotificationChannel $channel,
        string $deliveryId
    ): NotificationMessage {
        $subject = "Invoice {$invoice->document_number} from {$organization->name}";

        $lines = $invoice->lines()->orderBy('created_at')->orderBy('id')->get();

        $bodyLines = [
            "Invoice {$invoice->document_number}",
            "Issued by: {$organization->name}",
            'Issued on: '.($invoice->issued_at?->format('Y-m-d') ?? ''),
            'Due on: '.($invoice->due_on?->format('Y-m-d') ?? 'On receipt'),
            'Customer: '.($invoice->customer_name ?? '-'),
            '',
            'Lines:',
        ];

        foreach ($lines as $line) {
            $bodyLines[] = "- {$line->description}: {$line->quantity()->toString()} x "
                ."{$line->unitPrice()->toString()} = {$line->lineTotal()->toString()}";
        }

        $bodyLines[] = '';
        $bodyLines[] = 'Subtotal: '.UnitPrice::of($invoice->subtotal)->toString();
        $bodyLines[] = 'Discount: '.UnitPrice::of($invoice->discount_total)->toString();
        $bodyLines[] = "Tax: {$invoice->taxTotal()->toString()}";
        $bodyLines[] = "Total due: {$invoice->grandTotal()->toString()} {$invoice->currency}";

        if ($channel === NotificationChannel::Email) {
            $bodyLines[] = '';
            $bodyLines[] = 'This message was generated by flowOS.';
        }

        return new NotificationMessage(
            $subject,
            implode("\n", $bodyLines),
            $channel,
            'invoice',
            $invoice->id,
            $deliveryId,
        );
    }

    /**
     * Validate and price draft lines. Tax is not applied to drafts; issue
     * computes the tax snapshot (ADR 0036).
     *
     * @return array{
     *     lines: array<int, array{product: Product|null, description: string, quantity: Quantity, unit_price: UnitPrice, discount: UnitPrice, line_total: ScaledDecimal}>,
     *     subtotal: ScaledDecimal,
     *     discount_total: ScaledDecimal
     * }
     */
    private function prepareLines(Organization $organization, array $input): array
    {
        $lines = [];
        $subtotal = ScaledDecimal::of(0, UnitPrice::SCALE);
        $discountTotal = ScaledDecimal::of(0, UnitPrice::SCALE);

        foreach ($input as $row) {
            $product = null;

            if (! empty($row['product_id'])) {
                $product = $this->resolveProduct($organization, $row['product_id']);
            }

            $quantity = Quantity::parse($row['quantity']);

            if ($quantity->isNegative() || $quantity->isZero()) {
                throw new LogicException('An invoice line quantity must be a positive magnitude.');
            }

            $unitPrice = UnitPrice::parse($row['unit_price']);

            if ($unitPrice->isNegative()) {
                throw new LogicException('The unit price must not be negative.');
            }

            $discount = isset($row['discount']) && $row['discount'] !== null
                ? UnitPrice::parse($row['discount'])
                : UnitPrice::of(0);

            if ($discount->isNegative()) {
                throw new LogicException('The discount must not be negative.');
            }

            $lineTotal = ScaledDecimal::of(
                DecimalMath::multiplyRoundHalfUp(
                    $quantity->scaledValue(),
                    $unitPrice->scaledValue(),
                    Quantity::SCALE,
                    UnitPrice::SCALE,
                    UnitPrice::SCALE
                ),
                UnitPrice::SCALE
            )->subtract(ScaledDecimal::of($discount->scaledValue(), UnitPrice::SCALE));

            if ($lineTotal->isNegative()) {
                throw new LogicException('The discount must not exceed the line amount.');
            }

            $lines[] = [
                'product' => $product,
                'description' => $row['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ];

            $subtotal = $subtotal->add($lineTotal);
            $discountTotal = $discountTotal->add(ScaledDecimal::of($discount->scaledValue(), UnitPrice::SCALE));
        }

        return ['lines' => $lines, 'subtotal' => $subtotal, 'discount_total' => $discountTotal];
    }

    private function resolveCustomer(Organization $organization, string $id): Customer
    {
        $customer = Customer::query()->where('organization_id', $organization->id)->find($id);

        if (! $customer) {
            throw new LogicException('The customer does not belong to this organization.');
        }

        if ($customer->isArchived()) {
            throw new LogicException('An archived customer cannot be invoiced.');
        }

        return $customer;
    }

    private function resolveBranch(Organization $organization, string $id): Branch
    {
        $branch = Branch::query()->where('organization_id', $organization->id)->find($id);

        if (! $branch) {
            throw new LogicException('The branch does not belong to this organization.');
        }

        return $branch;
    }

    private function resolveProduct(Organization $organization, string $id): Product
    {
        $product = Product::query()->where('organization_id', $organization->id)->find($id);

        if (! $product) {
            throw new LogicException('The product does not belong to this organization.');
        }

        if ($product->isArchived()) {
            throw new LogicException('An archived product cannot be invoiced.');
        }

        return $product;
    }
}
