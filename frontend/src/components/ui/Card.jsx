import { cn } from '@/utils/cn'

export const Card = ({ className, ...props }) => (
  <div
    className={cn('rounded-xl border border-border bg-card text-card-foreground shadow-soft', className)}
    {...props}
  />
)

export const CardHeader = ({ className, ...props }) => (
  <div className={cn('flex flex-wrap items-start justify-between gap-3 px-5 pb-3 pt-5', className)} {...props} />
)

export const CardTitle = ({ className, ...props }) => (
  <h3 className={cn('text-[15px] font-semibold leading-6 text-foreground', className)} {...props} />
)

export const CardDescription = ({ className, ...props }) => (
  <p className={cn('mt-1 text-sm text-muted-foreground', className)} {...props} />
)

export const CardContent = ({ className, ...props }) => (
  <div className={cn('px-5 pb-5', className)} {...props} />
)

export const CardFooter = ({ className, ...props }) => (
  <div className={cn('flex items-center gap-3 border-t border-border px-5 py-3', className)} {...props} />
)
