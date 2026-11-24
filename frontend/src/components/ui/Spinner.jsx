import { cn } from '@/utils/cn'

export const Spinner = ({ className, label = 'Loading' }) => (
  <svg
    className={cn('h-5 w-5 animate-spin text-highlight', className)}
    viewBox="0 0 24 24"
    fill="none"
    role="status"
    aria-label={label}
  >
    <circle className="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
    <path className="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
  </svg>
)

export const PageLoader = () => (
  <div className="flex min-h-[50vh] items-center justify-center">
    <div className="flex flex-col items-center gap-3">
      <Spinner className="h-8 w-8" />
      <p className="text-sm text-muted-foreground">Loading…</p>
    </div>
  </div>
)
