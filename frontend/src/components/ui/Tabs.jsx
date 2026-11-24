import { cn } from '@/utils/cn'

export const Tabs = ({ tabs, value, onChange, className }) => (
  <div className={cn('flex gap-1 overflow-x-auto border-b border-border scrollbar-thin', className)}>
    {tabs.map((tab) => {
      const active = tab.key === value
      return (
        <button
          key={tab.key}
          type="button"
          onClick={() => onChange(tab.key)}
          className={cn(
            'relative whitespace-nowrap px-3 pb-3 pt-2 text-sm font-medium transition',
            active ? 'text-highlight' : 'text-muted-foreground hover:text-foreground',
          )}
        >
          {tab.label}
          {tab.count !== undefined && tab.count !== null && (
            <span
              className={cn(
                'ml-1.5 rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                active ? 'bg-primary/10 text-highlight' : 'bg-muted text-muted-foreground',
              )}
            >
              {tab.count}
            </span>
          )}
          {active && <span className="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-primary" />}
        </button>
      )
    })}
  </div>
)
