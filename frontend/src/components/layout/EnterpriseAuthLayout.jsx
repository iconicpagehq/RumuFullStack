import { Outlet } from 'react-router-dom'
import { CheckCircleIcon } from '@heroicons/react/20/solid'
import { Brand } from '@/components/shared/Brand'

const highlights = [
  'One workspace for admins, managers and sales employees',
  'Pipeline, assignments, tasks, site visits and inventory in one place',
  'Role-based access, team targets, reports and audit history',
]

export const EnterpriseAuthLayout = () => (
  <div className="grid min-h-screen lg:grid-cols-2">
    <div className="sidebar-surface relative hidden overflow-hidden border-r border-black/20 lg:flex lg:flex-col lg:justify-between lg:p-12">
      <div className="auth-grid absolute inset-0 opacity-30" aria-hidden="true" />
      <div className="absolute -right-24 top-24 h-80 w-80 rounded-full bg-brand-highlight/10 blur-3xl" aria-hidden="true" />
      <div className="relative">
        <Brand to="/" dark size="lg" />
      </div>
      <div className="relative max-w-md">
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/50">
          Enterprise edition
        </p>
        <h1 className="mt-3 text-balance text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-white">
          Run your entire sales organisation from one place.
        </h1>
        <p className="mt-4 text-sm leading-relaxed text-white/70">
          Rumu Enterprise gives real estate companies a structured workspace for teams,
          lead distribution, site visits, inventory and performance.
        </p>
        <ul className="mt-7 space-y-3">
          {highlights.map((item) => (
            <li key={item} className="flex items-start gap-3 text-sm text-white/80">
              <CheckCircleIcon className="mt-0.5 h-4 w-4 shrink-0 text-white/70" />
              <span>{item}</span>
            </li>
          ))}
        </ul>
      </div>
      <div className="relative flex items-center justify-between text-[11px] text-white/50">
        <span>© {new Date().getFullYear()} Rumu. All rights reserved.</span>
        <span>Built for real estate teams</span>
      </div>
    </div>

    <div className="flex min-h-screen flex-col bg-background">
      <div className="flex items-center justify-between p-5 lg:hidden">
        <Brand to="/" />
        <span className="text-xs font-medium text-muted-foreground">Enterprise edition</span>
      </div>
      <div className="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
        <div className="w-full max-w-[520px]">
          <Outlet />
        </div>
      </div>
      <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 px-5 py-5 text-xs text-muted-foreground">
        <span>© {new Date().getFullYear()} Rumu</span>
        <a href="/privacy-policy" className="transition hover:text-foreground">
          Privacy
        </a>
        <a href="/terms-of-service" className="transition hover:text-foreground">
          Terms
        </a>
      </div>
    </div>
  </div>
)
