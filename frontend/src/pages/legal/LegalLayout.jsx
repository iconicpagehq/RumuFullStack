import { Link } from 'react-router-dom'
import { ArrowLeftIcon } from '@heroicons/react/20/solid'
import { Brand } from '@/components/shared/Brand'

export const LegalLayout = ({ title, updatedAt, children }) => (
  <div className="min-h-screen bg-background">
    <header className="border-b border-border bg-card">
      <div className="mx-auto flex h-16 max-w-4xl items-center justify-between px-5">
        <Brand to="/" />
        <Link
          to="/login"
          className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground"
        >
          <ArrowLeftIcon className="h-4 w-4" />
          Back to sign in
        </Link>
      </div>
    </header>
    <main className="mx-auto max-w-3xl px-5 py-12">
      <p className="text-xs font-semibold uppercase tracking-[0.15em] text-highlight">Legal</p>
      <h1 className="mt-2 text-3xl font-bold tracking-tight text-foreground">{title}</h1>
      <p className="mt-2 text-sm text-muted-foreground">Last updated: {updatedAt}</p>
      <div className="prose-rumu mt-10 space-y-7 text-sm leading-7 text-muted-foreground [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-foreground [&_h3]:text-sm [&_h3]:font-semibold [&_h3]:text-foreground [&_li]:ml-5 [&_li]:list-disc [&_strong]:text-foreground">
        {children}
      </div>
    </main>
    <footer className="border-t border-border py-6 text-xs text-muted-foreground">
      <div className="mx-auto max-w-4xl px-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left">
        <p>© {new Date().getFullYear()} Rumu. All rights reserved.</p>
        <div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-1">
          <Link to="/privacy-policy" className="hover:text-foreground transition">Privacy Policy</Link>
          <Link to="/terms-of-service" className="hover:text-foreground transition">Terms of Service</Link>
          <Link to="/data-deletion" className="hover:text-foreground transition">Data Deletion</Link>
          <Link to="/contact" className="hover:text-foreground transition">Contact Us</Link>
        </div>
      </div>
    </footer>
  </div>
)
