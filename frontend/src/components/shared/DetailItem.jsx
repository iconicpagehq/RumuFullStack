import { cn } from '@/utils/cn'

export const DetailItem = ({ label, children, className }) => {
  const hasValue = children !== undefined && children !== null && children !== ''
  return (
    <div className={cn('min-w-0', className)}>
      <dt className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</dt>
      <dd className="mt-1 break-words text-sm font-medium text-foreground">{hasValue ? children : '—'}</dd>
    </div>
  )
}
