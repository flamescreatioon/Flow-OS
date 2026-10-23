<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

trait Searchable
{
    /**
     * Apply a case-insensitive substring search across the given columns.
     *
     * SQLite compares LIKE case-insensitively for ASCII while PostgreSQL
     * compares LIKE case-sensitively, so each connection is given the
     * operator that matches case-insensitively on it. Without this the same
     * filter returns different rows depending on the database.
     *
     * @param  array<int, string>  $columns
     */
    public function scopeSearch(Builder $query, string $term, array $columns): Builder
    {
        $needle = '%'.str_replace(['%', '_'], '', trim($term)).'%';
        $operator = $this->caseInsensitiveLikeOperator();

        return $query->where(function (Builder $query) use ($columns, $needle, $operator) {
            foreach ($columns as $column) {
                $this->guardSearchColumn($column);

                $query->orWhereRaw($column.' '.$operator.' ?', [$needle]);
            }
        });
    }

    /**
     * The SQL operator that performs a case-insensitive pattern match on the
     * current connection.
     */
    protected function caseInsensitiveLikeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /**
     * Search columns are interpolated into raw SQL, so they must be plain
     * column names rather than expressions or fragments.
     */
    protected function guardSearchColumn(string $column): void
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new InvalidArgumentException('Search columns must be plain column names.');
        }
    }
}
