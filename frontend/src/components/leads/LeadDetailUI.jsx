import {
  ChatBubbleLeftRightIcon,
  ChevronDownIcon,
  ClockIcon,
  PencilSquareIcon,
} from '@heroicons/react/24/outline'
import { Button } from '@/components/ui/Button'
import { Textarea } from '@/components/ui/Input'
import { cn } from '@/utils/cn'
import { formatDateTime, timeAgo } from '@/utils/formatters'

const toneStyles = {
  primary: 'bg-primary/10 text-highlight',
  success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
  warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
  info: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
  purple: 'bg-violet-500/10 text-violet-600 dark:text-violet-300',
  danger: 'bg-red-500/10 text-red-600 dark:text-red-300',
  neutral: 'bg-muted text-muted-foreground',
}

const actionStyles = {
  primary: {
    tile: 'border-primary/20 bg-primary/5 hover:border-primary/35 hover:bg-primary/10',
    icon: 'bg-primary/10 text-highlight',
  },
  success: {
    tile: 'border-emerald-500/20 bg-emerald-500/5 hover:border-emerald-500/35 hover:bg-emerald-500/10',
    icon: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
  },
  warning: {
    tile: 'border-amber-500/20 bg-amber-500/5 hover:border-amber-500/35 hover:bg-amber-500/10',
    icon: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
  },
  info: {
    tile: 'border-sky-500/20 bg-sky-500/5 hover:border-sky-500/35 hover:bg-sky-500/10',
    icon: 'bg-sky-500/10 text-sky-600 dark:text-sky-300',
  },
}

const whatsappNumber = (phone) => {
  const digits = String(phone || '').replace(/\D/g, '')
  if (digits.length === 10) return `91${digits}`
  return digits
}

export const LeadStageRail = ({
  steps,
  value,
  onSelect,
  canSelect = () => true,
  pending = false,
  className,
}) => (
  <div
    role="group"
    aria-label="Lead pipeline"
    className={cn(
      'flex gap-1 overflow-x-auto rounded-xl border border-border bg-card p-1.5 shadow-soft scrollbar-thin',
      className,
    )}
  >
    {steps.map((step) => {
      const active = step.value === value
      const enabled = !pending && canSelect(step.value)

      return (
        <button
          key={step.value}
          type="button"
          onClick={() => enabled && onSelect(step.value)}
          disabled={!enabled && !active}
          aria-current={active ? 'step' : undefined}
          className={cn(
            'min-w-fit flex-1 whitespace-nowrap rounded-lg px-3.5 py-2.5 text-sm font-medium transition',
            active
              ? 'bg-primary text-primary-foreground shadow-sm'
              : enabled
                ? 'text-muted-foreground hover:bg-muted hover:text-foreground'
                : 'cursor-not-allowed text-muted-foreground/45',
          )}
        >
          {step.label}
        </button>
      )
    })}
  </div>
)

export const LeadActionTiles = ({ phone, onNote, onActivity }) => {
  const wa = whatsappNumber(phone)
  const actions = [
    {
      key: 'whatsapp',
      label: 'WhatsApp',
      icon: ChatBubbleLeftRightIcon,
      tone: 'success',
      href: wa ? `https://wa.me/${wa}` : null,
      external: true,
    },
    { key: 'note', label: 'Note', icon: PencilSquareIcon, tone: 'warning', onClick: onNote },
    { key: 'activity', label: 'Activity', icon: ClockIcon, tone: 'info', onClick: onActivity },
  ]

  return (
    <div className="grid grid-cols-3 gap-2">
      {actions.map((action) => {
        const style = actionStyles[action.tone]
        const ActionIcon = action.icon
        const content = (
          <>
            <span className={cn('flex h-9 w-9 items-center justify-center rounded-full', style.icon)}>
              <ActionIcon className="h-4 w-4" />
            </span>
            <span className="text-[11px] font-medium text-foreground">{action.label}</span>
          </>
        )
        const classes = cn(
          'flex flex-col items-center gap-1.5 rounded-xl border px-1 py-2.5 text-center transition',
          style.tile,
          !action.href && !action.onClick && 'cursor-not-allowed opacity-50',
          action.disabled && 'pointer-events-none opacity-60',
        )

        if (action.href) {
          return (
            <a
              key={action.key}
              href={action.href}
              target={action.external ? '_blank' : undefined}
              rel={action.external ? 'noreferrer' : undefined}
              className={classes}
            >
              {content}
            </a>
          )
        }

        return (
          <button
            key={action.key}
            type="button"
            onClick={action.onClick}
            disabled={!action.onClick || action.disabled}
            aria-busy={action.disabled || undefined}
            className={classes}
          >
            {content}
          </button>
        )
      })}
    </div>
  )
}

const TransitionPill = ({ fromLabel, toLabel }) => (
  <div className="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-medium">
    <span className="rounded bg-red-500/10 px-2 py-0.5 text-red-600 dark:text-red-300">
      {fromLabel}
    </span>
    <span aria-hidden="true" className="text-muted-foreground">
      →
    </span>
    <span className="rounded bg-emerald-500/10 px-2 py-0.5 text-emerald-600 dark:text-emerald-300">
      {toLabel}
    </span>
  </div>
)

export const LeadTimeline = ({ items = [], empty = null }) => {
  if (!items.length) return empty

  return (
    <ol className="relative space-y-4">
      <span
        aria-hidden="true"
        className="absolute bottom-3 left-4 top-3 w-px -translate-x-1/2 bg-border"
      />
      {items.map((item) => {
        const Icon = item.icon || ClockIcon
        return (
          <li key={item.id} className="relative flex gap-3 sm:gap-4">
            <span
              className={cn(
                'relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-border shadow-soft',
                toneStyles[item.tone] || toneStyles.neutral,
              )}
            >
              <Icon className="h-4 w-4" />
            </span>
            <div className="min-w-0 flex-1 rounded-xl border border-border bg-card p-4 shadow-soft">
              <div className="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                <p className="text-sm font-medium text-foreground">{item.title}</p>
                {item.time && (
                  <time
                    dateTime={item.time}
                    title={formatDateTime(item.time)}
                    className="whitespace-nowrap text-[11px] text-muted-foreground"
                  >
                    {timeAgo(item.time)}
                  </time>
                )}
              </div>
              {item.transition && (
                <TransitionPill
                  fromLabel={item.transition.fromLabel}
                  toLabel={item.transition.toLabel}
                />
              )}
              {item.description && (
                <p className="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                  {item.description}
                </p>
              )}
              {item.user && (
                <p className="mt-2 text-[11px] text-muted-foreground">By {item.user}</p>
              )}
            </div>
          </li>
        )
      })}
    </ol>
  )
}

export const LeadAbout = ({ items, open, onToggle }) => (
  <div className="rounded-xl border border-border bg-card">
    <button
      type="button"
      onClick={onToggle}
      aria-expanded={open}
      className="flex w-full items-center justify-between gap-3 px-3.5 py-3 text-left"
    >
      <span className="text-sm font-semibold text-foreground">About Lead</span>
      <ChevronDownIcon
        className={cn('h-4 w-4 text-muted-foreground transition', open && 'rotate-180')}
      />
    </button>
    {open && (
      <dl className="grid grid-cols-2 gap-x-3 gap-y-3 border-t border-border px-3.5 py-3">
        {items.map((item) => (
          <div key={item.label} className="min-w-0">
            <dt className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
              {item.label}
            </dt>
            <dd className="mt-0.5 break-words text-xs font-medium text-foreground">{item.value || '—'}</dd>
          </div>
        ))}
      </dl>
    )}
  </div>
)

export const LeadNoteComposer = ({ id, value, onChange, onSubmit, pending, placeholder }) => (
  <form
    onSubmit={(event) => {
      event.preventDefault()
      if (!value.trim()) return
      onSubmit()
    }}
    className="rounded-xl border border-border bg-muted/20 p-3"
  >
    <Textarea
      id={id}
      rows={3}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      placeholder={placeholder || 'Write a note…'}
      aria-label="Write a note"
      className="min-h-[84px] border-0 bg-transparent shadow-none focus:ring-0"
    />
    <div className="mt-2 flex items-center justify-end">
      <Button type="submit" size="sm" disabled={!value.trim()} loading={pending}>
        Post note
      </Button>
    </div>
  </form>
)
