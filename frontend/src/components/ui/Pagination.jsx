import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/20/solid'
import { formatNumber } from '@/utils/formatters'
import { cn } from '@/utils/cn'

const pageWindow = (current, last) => {
  const size = Math.min(5, Math.max(1, last))
  let start = Math.max(1, current - Math.floor(size / 2))
  const end = Math.min(last, start + size - 1)
  start = Math.max(1, end - size + 1)
  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
}

export const Pagination = ({ page = 1, lastPage = 1, total = 0, perPage = 20, onPageChange, className }) => {
  if (!lastPage || lastPage <= 1) {
    return total > 0 ? (
      <div className={cn('flex items-center justify-between px-1 py-3 text-xs text-muted-foreground', className)}>
        <span>{formatNumber(total)} total</span>
      </div>
    ) : null
  }

  const from = (page - 1) * perPage + 1
  const to = Math.min(page * perPage, total)
  const pages = pageWindow(page, lastPage)

  return (
    <div className={cn('flex flex-wrap items-center justify-between gap-3 px-1 py-3', className)}>
      <p className="text-xs text-muted-foreground">
        Showing <span className="font-medium text-foreground">{formatNumber(from)}</span>–
        <span className="font-medium text-foreground">{formatNumber(to)}</span> of{' '}
        <span className="font-medium text-foreground">{formatNumber(total)}</span>
      </p>
      <div className="flex items-center gap-1">
        <button
          type="button"
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-40"
          aria-label="Previous page"
        >
          <ChevronLeftIcon className="h-4 w-4" />
        </button>
        {pages[0] > 1 && (
          <>
            <PageButton page={1} active={false} onClick={onPageChange} />
            {pages[0] > 2 && <span className="px-1 text-xs text-muted-foreground">…</span>}
          </>
        )}
        {pages.map((item) => (
          <PageButton key={item} page={item} active={item === page} onClick={onPageChange} />
        ))}
        {pages[pages.length - 1] < lastPage && (
          <>
            {pages[pages.length - 1] < lastPage - 1 && (
              <span className="px-1 text-xs text-muted-foreground">…</span>
            )}
            <PageButton page={lastPage} active={false} onClick={onPageChange} />
          </>
        )}
        <button
          type="button"
          onClick={() => onPageChange(page + 1)}
          disabled={page >= lastPage}
          className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:bg-muted disabled:pointer-events-none disabled:opacity-40"
          aria-label="Next page"
        >
          <ChevronRightIcon className="h-4 w-4" />
        </button>
      </div>
    </div>
  )
}

const PageButton = ({ page, active, onClick }) => (
  <button
    type="button"
    onClick={() => onClick(page)}
    className={cn(
      'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-medium transition',
      active
        ? 'bg-primary text-primary-foreground shadow-sm'
        : 'border border-border text-foreground hover:bg-muted',
    )}
  >
    {page}
  </button>
)
