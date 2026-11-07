<?php

namespace App\Application\Finance;

use App\Application\Audit\AuditLogger;
use App\Application\Documents\DocumentNumberService;
use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ExpenseStatus;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Operational expenses (ADR 0039).
 *
 * A recorded expense is editable; void is terminal and the record is kept as
 * evidence. Expenses never touch stock or the ledger — they are posting
 * inputs for a future accounting integration. There is no delete path.
 */
final class ExpenseService
{
    public function create(Organization $organization, array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $category = $this->resolveCategory($organization, $data['expense_category_id']);

            $branch = null;

            if (! empty($data['branch_id'])) {
                $branch = $this->resolveBranch($organization, $data['branch_id']);
            }

            $amount = $this->resolveAmount($data['amount']);
            $number = app(DocumentNumberService::class)->allocateNext($organization, DocumentNumberService::EXPENSE);

            $expense = Expense::create([
                'organization_id' => $organization->id,
                'branch_id' => $branch?->id,
                'expense_category_id' => $category->id,
                'expense_number' => $number,
                'document_number' => app(DocumentNumberService::class)->render(DocumentNumberService::EXPENSE, $number),
                'status' => ExpenseStatus::Recorded->value,
                'description' => $data['description'],
                'amount' => $amount->scaledValue(),
                'spent_on' => $data['spent_on'] ?? now()->format('Y-m-d'),
                'payee' => $data['payee'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
            ]);

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'finance.expense_recorded',
                'expense',
                $expense->id,
                [
                    'organization_id' => $organization->id,
                    'document_number' => $expense->document_number,
                    'expense_category_id' => $category->id,
                    'amount' => $amount->toString(),
                ]
            );

            return $expense->load(['category', 'branch']);
        });
    }

    public function update(Organization $organization, Expense $expense, array $data, User $actor): Expense
    {
        if ($expense->organization_id !== $organization->id) {
            throw new LogicException('The expense does not belong to this organization.');
        }

        if (! $expense->isRecorded()) {
            throw new LogicException('A voided expense cannot be edited.');
        }

        return DB::transaction(function () use ($organization, $expense, $data, $actor) {
            if (array_key_exists('expense_category_id', $data)) {
                $expense->expense_category_id = $this->resolveCategory($organization, $data['expense_category_id'])->id;
            }

            if (array_key_exists('branch_id', $data)) {
                $expense->branch_id = $data['branch_id'] === null
                    ? null
                    : $this->resolveBranch($organization, $data['branch_id'])->id;
            }

            if (array_key_exists('description', $data)) {
                $expense->description = $data['description'];
            }

            if (array_key_exists('amount', $data)) {
                $expense->amount = $this->resolveAmount($data['amount'])->scaledValue();
            }

            if (array_key_exists('spent_on', $data)) {
                $expense->spent_on = $data['spent_on'];
            }

            if (array_key_exists('payee', $data)) {
                $expense->payee = $data['payee'];
            }

            if (array_key_exists('payment_method', $data)) {
                $expense->payment_method = $data['payment_method'];
            }

            if (array_key_exists('note', $data)) {
                $expense->note = $data['note'];
            }

            $expense->save();

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'finance.expense_updated',
                'expense',
                $expense->id,
                ['organization_id' => $organization->id]
            );

            return $expense->load(['category', 'branch']);
        });
    }

    public function void(Organization $organization, Expense $expense, User $actor): Expense
    {
        if ($expense->organization_id !== $organization->id) {
            throw new LogicException('The expense does not belong to this organization.');
        }

        if ($expense->isVoided()) {
            throw new LogicException('The expense is already voided.');
        }

        return DB::transaction(function () use ($organization, $expense, $actor) {
            $expense->forceFill([
                'status' => ExpenseStatus::Voided->value,
                'voided_at' => now(),
            ])->save();

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'finance.expense_voided',
                'expense',
                $expense->id,
                ['organization_id' => $organization->id]
            );

            return $expense->load(['category', 'branch']);
        });
    }

    private function resolveCategory(Organization $organization, string $id): ExpenseCategory
    {
        $category = ExpenseCategory::query()->where('organization_id', $organization->id)->find($id);

        if (! $category) {
            throw new LogicException('The expense category does not belong to this organization.');
        }

        if ($category->isArchived()) {
            throw new LogicException('An archived expense category cannot be used.');
        }

        return $category;
    }

    private function resolveBranch(Organization $organization, string $id): Branch
    {
        $branch = Branch::query()->where('organization_id', $organization->id)->find($id);

        if (! $branch) {
            throw new LogicException('The branch does not belong to this organization.');
        }

        return $branch;
    }

    /**
     * The amount is always a positive magnitude at the unit price scale; the
     * server rejects anything else rather than trusting the client.
     */
    private function resolveAmount(string $amount): UnitPrice
    {
        $parsed = UnitPrice::parse($amount);

        if ($parsed->isNegative() || $parsed->isZero()) {
            throw new LogicException('The expense amount must be a positive value.');
        }

        return $parsed;
    }
}
