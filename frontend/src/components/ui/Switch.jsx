import { Switch as HeadlessSwitch } from '@headlessui/react'
import { cn } from '@/utils/cn'

export const Switch = ({ checked, onChange, label, description, disabled = false, className }) => (
  <div className={cn('flex items-start justify-between gap-4', className)}>
    {(label || description) && (
      <div className="min-w-0">
        {label && <p className="text-sm font-medium text-foreground">{label}</p>}
        {description && <p className="mt-0.5 text-xs text-muted-foreground">{description}</p>}
      </div>
    )}
    <HeadlessSwitch
      checked={Boolean(checked)}
      onChange={onChange}
      disabled={disabled}
      className={cn(
        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
        checked ? 'border-highlight bg-primary' : 'border-border bg-muted',
      )}
    >
      <span
        className={cn(
          'inline-block h-4 w-4 transform rounded-full bg-white shadow transition',
          checked ? 'translate-x-6' : 'translate-x-1',
        )}
      />
    </HeadlessSwitch>
  </div>
)
