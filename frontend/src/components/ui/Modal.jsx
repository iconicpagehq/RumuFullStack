import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/react'
import { XMarkIcon } from '@heroicons/react/24/outline'
import { cn } from '@/utils/cn'

const sizes = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
}

export const Modal = ({
  open,
  onClose,
  title,
  description,
  size = 'md',
  footer,
  children,
  className,
  hideClose = false,
}) => (
  <Dialog open={open} onClose={onClose} className="relative z-50">
    <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-[2px] animate-fade-in" aria-hidden="true" />
    <div className="fixed inset-0 overflow-y-auto">
      <div className="flex min-h-full items-center justify-center p-4">
        <DialogPanel
          className={cn(
            'w-full rounded-2xl border border-border bg-card p-0 shadow-card animate-slide-up',
            sizes[size] || sizes.md,
            className,
          )}
        >
          {(title || !hideClose) && (
            <div className="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
              <div>
                {title && (
                  <DialogTitle className="text-base font-semibold text-foreground">{title}</DialogTitle>
                )}
                {description && (
                  <DialogDescription className="mt-1 text-sm text-muted-foreground">
                    {description}
                  </DialogDescription>
                )}
              </div>
              {!hideClose && (
                <button
                  type="button"
                  onClick={onClose}
                  className="rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                  aria-label="Close"
                >
                  <XMarkIcon className="h-5 w-5" />
                </button>
              )}
            </div>
          )}
          <div className="px-5 py-4">{children}</div>
          {footer && (
            <div className="flex flex-wrap items-center justify-end gap-3 border-t border-border px-5 py-3">
              {footer}
            </div>
          )}
        </DialogPanel>
      </div>
    </div>
  </Dialog>
)
