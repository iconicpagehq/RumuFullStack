import { HeaderActions } from './HeaderActions'

export const Topbar = ({ onMenuClick }) => (
  <header className="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-border bg-card/90 px-4 backdrop-blur-md lg:hidden">
    <button
      type="button"
      onClick={onMenuClick}
      className="inline-flex h-10 w-10 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-muted hover:text-foreground"
      aria-label="Open navigation"
    >
      <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M4 7h16M4 12h16M4 17h16" strokeLinecap="round" />
      </svg>
    </button>
    <HeaderActions />
  </header>
)
