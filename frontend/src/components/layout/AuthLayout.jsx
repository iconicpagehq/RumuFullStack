import { Link, Outlet, useLocation } from 'react-router-dom'
import { CheckCircleIcon } from '@heroicons/react/20/solid'
import { Brand } from '@/components/shared/Brand'
import { cn } from '@/utils/cn'

const highlights = [
  'Capture, qualify and convert leads in one workspace',
  'Import from Meta and CSV with automatic CAPI tracking',
  'Give managers, teams and agents the right level of access',
]

export const AuthLayout = () => {
  const { pathname } = useLocation()
  const isRegister = pathname.startsWith('/register')

  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      <div className="sidebar-surface relative hidden overflow-hidden border-r border-black/20 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div className="auth-grid absolute inset-0 opacity-30" aria-hidden="true" />
        <div className="absolute -right-24 top-24 h-80 w-80 rounded-full bg-brand-highlight/10 blur-3xl" aria-hidden="true" />
        <div className="relative">
          <Brand to="/" dark size="lg" />
        </div>
        <div className="relative max-w-md">
          <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/50">Real estate workspace</p>
          <h1 className="mt-3 text-balance text-[32px] font-semibold leading-[1.15] tracking-[-0.02em] text-white">
            One workspace from first enquiry to closed deal.
          </h1>
          <p className="mt-4 text-sm leading-relaxed text-white/70">
            Rumu gives realtors and real estate teams a focused workspace for campaigns, leads,
            inventory and people — whether you work solo or run a growing organisation.
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
          <span>Built for modern real estate teams</span>
        </div>
      </div>

      <div className="flex min-h-screen flex-col bg-background">
        <div className="flex items-center justify-between p-5 lg:hidden">
          <Brand to="/" />
          <span className="text-xs font-medium text-muted-foreground">Real estate workspace</span>
        </div>
        <div className="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
          <div className={cn('w-full', isRegister ? 'max-w-[560px]' : 'max-w-[420px]')}>
            <Outlet />
          </div>
        </div>
        <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 px-5 py-5 text-xs text-muted-foreground">
          <span>© {new Date().getFullYear()} Rumu</span>
          <Link to="/privacy-policy" className="transition hover:text-foreground">
            Privacy Policy
          </Link>
          <Link to="/terms-of-service" className="transition hover:text-foreground">
            Terms of Service
          </Link>
          <Link to="/data-deletion" className="transition hover:text-foreground">
            Data Deletion
          </Link>
          <Link to="/contact" className="transition hover:text-foreground">
            Contact Us
          </Link>
        </div>
      </div>
    </div>
  )
}
