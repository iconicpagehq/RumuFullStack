import { Link } from 'react-router-dom'
import { ChevronRightIcon } from '@heroicons/react/20/solid'
import { HeaderActions } from '@/components/layout/HeaderActions'
import { cn } from '@/utils/cn'

export const PageHeader = ({ title, description, actions, breadcrumbs, className }) => (
  <div className={cn('mb-6 flex flex-wrap items-start justify-between gap-4', className)}>
    <div className="min-w-0">
      {breadcrumbs?.length > 0 && (
        <nav className="mb-2 flex items-center gap-1 text-xs text-muted-foreground" aria-label="Breadcrumb">
          {breadcrumbs.map((crumb, index) => (
            <span key={`${crumb.label}-${index}`} className="flex items-center gap-1">
              {index > 0 && <ChevronRightIcon className="h-3 w-3 opacity-60" />}
              {crumb.to ? (
                <Link to={crumb.to} className="transition hover:text-foreground">
                  {crumb.label}
                </Link>
              ) : (
                <span className="text-foreground">{crumb.label}</span>
              )}
            </span>
          ))}
        </nav>
      )}
      <h1 className="text-2xl font-semibold tracking-[-0.02em] text-foreground sm:text-[26px]">
        {title}
      </h1>
      {description && <p className="mt-1 max-w-2xl text-sm text-muted-foreground">{description}</p>}
    </div>
    <div className="flex flex-wrap items-center gap-2">
      {actions}
      {actions && <span className="hidden h-6 w-px bg-border lg:block" aria-hidden="true" />}
      <div className="hidden lg:block">
        <HeaderActions />
      </div>
    </div>
  </div>
)
