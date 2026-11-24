import { InboxIcon } from '@heroicons/react/24/outline'
import { cn } from '@/utils/cn'

export const EmptyState = ({ icon: Icon = InboxIcon, title = 'Nothing here yet', description, action, className }) => (
  <div className={cn('flex flex-col items-center justify-center px-6 py-14 text-center', className)}>
    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
      <Icon className="h-6 w-6" />
    </div>
    <h3 className="mt-4 text-sm font-semibold text-foreground">{title}</h3>
    {description && <p className="mt-1 max-w-sm text-sm text-muted-foreground">{description}</p>}
    {action && <div className="mt-5">{action}</div>}
  </div>
)
