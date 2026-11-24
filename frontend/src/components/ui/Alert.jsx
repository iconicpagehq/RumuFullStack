import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { cn } from '@/utils/cn'

const variants = {
  info: {
    wrap: 'border-sky-500/20 bg-sky-500/10 text-sky-800 dark:text-sky-200',
    icon: InformationCircleIcon,
  },
  success: {
    wrap: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200',
    icon: CheckCircleIcon,
  },
  warning: {
    wrap: 'border-amber-500/25 bg-amber-500/10 text-amber-800 dark:text-amber-200',
    icon: ExclamationTriangleIcon,
  },
  danger: {
    wrap: 'border-red-500/20 bg-red-500/10 text-red-800 dark:text-red-200',
    icon: XCircleIcon,
  },
}

export const Alert = ({ variant = 'info', title, children, onDismiss, className }) => {
  const tone = variants[variant] || variants.info
  const Icon = tone.icon

  return (
    <div className={cn('flex gap-3 rounded-xl border p-3.5 text-sm', tone.wrap, className)} role="alert">
      <Icon className="mt-0.5 h-5 w-5 shrink-0 opacity-80" />
      <div className="min-w-0 flex-1">
        {title && <p className="font-semibold">{title}</p>}
        {children && <div className={cn('leading-relaxed', title && 'mt-0.5 opacity-90')}>{children}</div>}
      </div>
      {onDismiss && (
        <button
          type="button"
          onClick={onDismiss}
          className="shrink-0 rounded p-0.5 opacity-60 transition hover:opacity-100"
          aria-label="Dismiss"
        >
          <XMarkIcon className="h-4 w-4" />
        </button>
      )}
    </div>
  )
}
