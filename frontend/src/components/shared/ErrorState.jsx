import { ExclamationTriangleIcon } from '@heroicons/react/24/outline'
import { Button } from '@/components/ui/Button'

export const ErrorState = ({ error, onRetry, title = 'We could not load this page' }) => (
  <div className="flex flex-col items-center justify-center rounded-xl border border-border bg-card px-6 py-14 text-center">
    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10 text-destructive">
      <ExclamationTriangleIcon className="h-6 w-6" />
    </div>
    <h3 className="mt-4 text-sm font-semibold text-foreground">{title}</h3>
    <p className="mt-1 max-w-md text-sm text-muted-foreground">
      {error?.userMessage || error?.message || 'An unexpected error occurred.'}
    </p>
    {onRetry && (
      <Button variant="outline" size="sm" className="mt-5" onClick={onRetry}>
        Try again
      </Button>
    )}
  </div>
)
