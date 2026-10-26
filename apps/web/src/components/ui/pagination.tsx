'use client';

import { Button } from '@/components/ui/button';
import { cn } from '@/utils/cn';

export interface PaginationProps {
  currentPage: number;
  lastPage: number;
  disabled?: boolean;
  onPageChange: (page: number) => void;
}

type PageToken = { kind: 'page'; page: number } | { kind: 'gap' };

/**
 * First page, last page, and the current page with one neighbour on each
 * side, with gaps collapsed into an ellipsis.
 */
export function pageTokens(current: number, last: number): PageToken[] {
  if (last <= 1) return [];
  const wanted = new Set(
    [1, last, current - 1, current, current + 1].filter(
      (page) => page >= 1 && page <= last,
    ),
  );
  const tokens: PageToken[] = [];
  let previous = 0;
  for (const page of [...wanted].sort((a, b) => a - b)) {
    if (previous && page - previous > 1) tokens.push({ kind: 'gap' });
    tokens.push({ kind: 'page', page });
    previous = page;
  }
  return tokens;
}

export function Pagination({
  currentPage,
  lastPage,
  disabled = false,
  onPageChange,
}: PaginationProps) {
  if (lastPage <= 1) return null;

  return (
    <nav aria-label="Pagination" className="flex items-center gap-2 text-sm text-on-surface-variant">
      <Button
        variant="secondary"
        disabled={disabled || currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
      >
        Previous
      </Button>
      {pageTokens(currentPage, lastPage).map((token, index) =>
        token.kind === 'gap' ? (
          <span key={`gap-${index}`} aria-hidden="true">
            …
          </span>
        ) : (
          <Button
            key={token.page}
            variant={token.page === currentPage ? 'primary' : 'secondary'}
            disabled={disabled}
            aria-current={token.page === currentPage ? 'page' : undefined}
            aria-label={`Page ${token.page}`}
            onClick={() => onPageChange(token.page)}
          >
            {token.page}
          </Button>
        ),
      )}
      <Button
        variant="secondary"
        disabled={disabled || currentPage >= lastPage}
        onClick={() => onPageChange(currentPage + 1)}
      >
        Next
      </Button>
      <span className={cn('ml-1')} aria-live="polite">
        Page {currentPage} of {lastPage}
      </span>
    </nav>
  );
}
