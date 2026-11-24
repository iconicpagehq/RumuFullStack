import { Badge } from '@/components/ui/Badge'
import { stageLabel } from '@/utils/enterprise'

const tones = {
  new: 'info',
  contacted: 'info',
  qualified: 'primary',
  site_visit_scheduled: 'purple',
  site_visit_completed: 'purple',
  negotiation: 'warning',
  won: 'success',
  lost: 'danger',
  on_hold: 'neutral',
}

export const StageBadge = ({ stage, className }) => (
  <Badge variant={tones[stage] || 'neutral'} dot className={className}>
    {stageLabel(stage)}
  </Badge>
)

const priorityTones = {
  low: 'neutral',
  medium: 'info',
  high: 'warning',
  urgent: 'danger',
}

export const PriorityBadge = ({ priority, className }) => {
  if (!priority) return <span className="text-muted-foreground">—</span>
  return (
    <Badge variant={priorityTones[priority] || 'neutral'} className={className}>
      {priority.charAt(0).toUpperCase() + priority.slice(1)}
    </Badge>
  )
}
