import { render, screen, fireEvent } from '@testing-library/react';
import { Pagination, pageTokens } from '@/components/ui/pagination';

describe('pageTokens', () => {
  it('returns every page when the range is short', () => {
    expect(pageTokens(2, 4)).toEqual([
      { kind: 'page', page: 1 },
      { kind: 'page', page: 2 },
      { kind: 'page', page: 3 },
      { kind: 'page', page: 4 },
    ]);
  });

  it('collapses distant pages into gaps', () => {
    expect(pageTokens(5, 12)).toEqual([
      { kind: 'page', page: 1 },
      { kind: 'gap' },
      { kind: 'page', page: 4 },
      { kind: 'page', page: 5 },
      { kind: 'page', page: 6 },
      { kind: 'gap' },
      { kind: 'page', page: 12 },
    ]);
  });

  it('returns no tokens for a single page', () => {
    expect(pageTokens(1, 1)).toEqual([]);
  });
});

describe('Pagination', () => {
  it('renders nothing when there is only one page', () => {
    const { container } = render(
      <Pagination currentPage={1} lastPage={1} onPageChange={jest.fn()} />,
    );
    expect(container).toBeEmptyDOMElement();
  });

  it('reports the clicked page and the current page state', () => {
    const onPageChange = jest.fn();
    render(
      <Pagination currentPage={3} lastPage={8} onPageChange={onPageChange} />,
    );

    expect(screen.getByRole('navigation', { name: 'Pagination' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Page 3' })).toHaveAttribute(
      'aria-current',
      'page',
    );

    // The window renders the current page with one neighbour on each side.
    fireEvent.click(screen.getByRole('button', { name: 'Page 4' }));
    expect(onPageChange).toHaveBeenCalledWith(4);

    fireEvent.click(screen.getByRole('button', { name: 'Next' }));
    expect(onPageChange).toHaveBeenCalledWith(4);

    fireEvent.click(screen.getByRole('button', { name: 'Previous' }));
    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  it('disables navigation when loading', () => {
    render(
      <Pagination currentPage={2} lastPage={5} disabled onPageChange={jest.fn()} />,
    );
    expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Page 3' })).toBeDisabled();
  });
});
