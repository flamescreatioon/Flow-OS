<?php

namespace App\Application\Documents;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Allocates organization-scoped sequential document numbers (ADR 0035).
 *
 * Document numbers are business identifiers, separate from the UUID entity
 * identity. The sequence row is locked inside the caller's transaction, so
 * two concurrent allocations cannot hand out the same number, and a rolled
 * back transaction returns its number to the pool. This service must be
 * called inside the transaction of the document being written.
 */
final class DocumentNumberService
{
    public const GOODS_RECEIPT = 'goods_receipt';

    public const INVOICE = 'invoice';

    public const EXPENSE = 'expense';

    /**
     * The wire format prefix for each document type.
     */
    private const PREFIXES = [
        self::GOODS_RECEIPT => 'GRN',
        self::INVOICE => 'INV',
        self::EXPENSE => 'EXP',
    ];

    public function allocateNext(Organization $organization, string $documentType): int
    {
        if (! isset(self::PREFIXES[$documentType])) {
            throw new RuntimeException("Unknown document type [{$documentType}].");
        }

        // The insert is idempotent against the (organization_id,
        // document_type) unique constraint, so a concurrent first allocation
        // cannot fail the transaction on the insert itself.
        DB::table('document_sequences')->insertOrIgnore([
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => $organization->id,
            'document_type' => $documentType,
            'next_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('document_sequences')
            ->where('organization_id', $organization->id)
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            // Only reachable if the insert raced an intervening delete, which
            // no code path performs.
            throw new RuntimeException('The document sequence could not be reserved.');
        }

        DB::table('document_sequences')
            ->where('id', $sequence->id)
            ->update([
                'next_value' => $sequence->next_value + 1,
                'updated_at' => now(),
            ]);

        return (int) $sequence->next_value;
    }

    /**
     * Render a document number for the wire, for example GRN-000042.
     */
    public function render(string $documentType, int $number): string
    {
        if (! isset(self::PREFIXES[$documentType])) {
            throw new RuntimeException("Unknown document type [{$documentType}].");
        }

        return sprintf('%s-%06d', self::PREFIXES[$documentType], $number);
    }
}
