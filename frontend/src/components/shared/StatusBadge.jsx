import { Badge } from '@/components/ui/Badge'
import { titleCase } from '@/utils/formatters'

const tones = {
  active: 'success',
  approved: 'success',
  completed: 'success',
  connected: 'success',
  delivered: 'success',
  paid: 'success',
  resolved: 'success',
  sent: 'success',
  sold: 'success',
  success: 'success',
  verified: 'success',
  won: 'success',
  available: 'success',
  published: 'success',
  booked: 'warning',
  pending: 'warning',
  processing: 'warning',
  review: 'warning',
  in_progress: 'warning',
  queued: 'warning',
  draft: 'neutral',
  new: 'info',
  open: 'info',
  contacted: 'info',
  qualified: 'primary',
  negotiation: 'purple',
  trial: 'info',
  paused: 'neutral',
  on_hold: 'neutral',
  inactive: 'neutral',
  closed: 'neutral',
  archived: 'neutral',
  failed: 'danger',
  expired: 'danger',
  cancelled: 'danger',
  canceled: 'danger',
  rejected: 'danger',
  lost: 'danger',
  overdue: 'danger',
  suspended: 'danger',
  blocked: 'danger',
  bounced: 'danger',
  disabled: 'danger',
  unsubscribed: 'danger',
  read: 'neutral',
  unread: 'primary',
}

const labels = {
  in_progress: 'In progress',
  on_hold: 'On hold',
  unsubscribed: 'Unsubscribed',
}

export const StatusBadge = ({ status, label, variant, className }) => {
  if (!status && !label) return <span className="text-muted-foreground">—</span>
  const key = String(status || '').toLowerCase().replace(/[\s-]+/g, '_')
  const tone = variant || tones[key] || 'neutral'

  return (
    <Badge variant={tone} dot className={className}>
      {label || labels[key] || titleCase(key)}
    </Badge>
  )
}
