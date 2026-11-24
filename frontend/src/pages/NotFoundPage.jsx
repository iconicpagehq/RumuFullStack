import { Link } from 'react-router-dom'
import { ArrowLeftIcon, HomeIcon } from '@heroicons/react/20/solid'
import { Brand } from '@/components/shared/Brand'

export const NotFoundPage = () => (
  <div className="flex min-h-screen flex-col bg-background">
    <header className="border-b border-border bg-card">
      <div className="mx-auto flex h-16 max-w-5xl items-center px-5">
        <Brand to="/" />
      </div>
    </header>
    <main className="flex flex-1 items-center justify-center px-5 py-16">
      <div className="max-w-md text-center">
        <p className="text-7xl font-black tracking-tighter text-highlight/20">404</p>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-foreground">Page not found</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          The page you are looking for may have been moved, deleted, or the link is incorrect.
        </p>
        <div className="mt-7 flex flex-wrap justify-center gap-3">
          <Link
            to="/"
            className="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
          >
            <HomeIcon className="h-4 w-4" />
            Go home
          </Link>
          <button
            type="button"
            onClick={() => window.history.back()}
            className="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-4 text-sm font-semibold text-foreground transition hover:bg-muted"
          >
            <ArrowLeftIcon className="h-4 w-4" />
            Go back
          </button>
        </div>
      </div>
    </main>
  </div>
)
