import { Link, Outlet } from 'react-router-dom'
import { LockClosedIcon } from '@heroicons/react/20/solid'
import { Brand } from '@/components/shared/Brand'

export const CheckoutLayout = () => (
  <div className="flex min-h-screen flex-col bg-muted/30">
    <header className="border-b border-border bg-background">
      <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
        <Brand to="/realtor/dashboard" />
        <div className="flex items-center gap-4">
          <span className="hidden items-center gap-1.5 text-xs font-medium text-muted-foreground sm:inline-flex">
            <LockClosedIcon className="h-3.5 w-3.5" />
            Secure checkout
          </span>
          <Link
            to="/realtor/plans"
            className="text-xs font-semibold text-muted-foreground transition hover:text-foreground"
          >
            Back to plans
          </Link>
        </div>
      </div>
    </header>

    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
      <Outlet />
    </main>

    <footer className="border-t border-border py-5 text-center text-xs text-muted-foreground">
      Payments are processed securely by Razorpay · © {new Date().getFullYear()} Rumu
    </footer>
  </div>
)
