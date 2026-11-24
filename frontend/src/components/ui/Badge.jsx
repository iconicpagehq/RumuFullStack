import { cn } from '@/utils/cn'

const variants = {
  neutral: 'bg-muted text-muted-foreground ring-border',
  primary: 'bg-primary/10 text-highlight ring-highlight/20',
  success: 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300',
  warning: 'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:text-amber-300',
  danger: 'bg-red-500/10 text-red-700 ring-red-500/20 dark:text-red-300',
  info: 'bg-sky-500/10 text-sky-700 ring-sky-500/20 dark:text-sky-300',
  purple: 'bg-violet-500/10 text-violet-700 ring-violet-500/20 dark:text-violet-300',
  outline: 'bg-transparent text-foreground ring-border',
}

export const Badge = ({ variant = 'neutral', className, dot = false, children, ...props }) => {
  const tone = variants[variant] || variants.neutral

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
        tone,
        className,
      )}
      {...props}
    >
      {dot && <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" />}
      {children}
    </span>
  )
}
