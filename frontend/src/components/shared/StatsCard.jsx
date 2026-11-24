import { ArrowDownRightIcon, ArrowUpRightIcon } from '@heroicons/react/20/solid'
import { Card } from '@/components/ui/Card'
import { cn } from '@/utils/cn'

const tones = {
  primary: {
    tile: 'bg-primary-800/10 text-primary-800 dark:bg-highlight/10 dark:text-highlight',
  },
  purple: {
    tile: 'bg-primary-800/10 text-primary-800 dark:bg-highlight/10 dark:text-highlight',
  },
  success: {
    tile: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
  },
  warning: {
    tile: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
  },
  info: {
    tile: 'bg-blue-500/10 text-blue-600 dark:text-blue-300',
  },
  danger: {
    tile: 'bg-red-500/10 text-red-600 dark:text-red-400',
  },
}

export const StatsCard = ({
  label,
  value,
  icon: Icon,
  hint,
  trend,
  trendLabel,
  tone = 'primary',
  loading = false,
  className,
  valueClassName,
}) => {
  const hasTrend = trend !== undefined && trend !== null && Number.isFinite(Number(trend))
  const positive = Number(trend) >= 0
  const style = tones[tone] || tones.primary
  const hasMeta = hasTrend || hint || trendLabel

  return (
    <Card className={cn('p-5', className)}>
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <p className="text-[13px] font-medium leading-5 text-muted-foreground">{label}</p>
          {loading ? (
            <div className="skeleton mt-2.5 h-7 w-24" />
          ) : (
            <p
              className={cn(
                'mt-2.5 text-[28px] font-semibold leading-none tracking-[-0.03em] tabular-nums text-foreground',
                valueClassName,
              )}
            >
              {value ?? '—'}
            </p>
          )}
          {hasMeta && (
            <div className="mt-2.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-muted-foreground">
              {hasTrend && (
                <span
                  className={cn(
                    'inline-flex items-center gap-0.5 font-semibold',
                    positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400',
                  )}
                >
                  {positive ? (
                    <ArrowUpRightIcon className="h-3.5 w-3.5" />
                  ) : (
                    <ArrowDownRightIcon className="h-3.5 w-3.5" />
                  )}
                  {Math.abs(Number(trend))}%
                </span>
              )}
              {hint && <span className="truncate">{hint}</span>}
              {trendLabel && <span>{trendLabel}</span>}
            </div>
          )}
        </div>
        {Icon && (
          <div
            className={cn(
              'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
              style.tile,
            )}
          >
            <Icon className="h-5 w-5" />
          </div>
        )}
      </div>
    </Card>
  )
}
