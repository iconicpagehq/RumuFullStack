import { Dialog, DialogPanel } from '@headlessui/react'
import { XMarkIcon } from '@heroicons/react/24/outline'
import { SidebarNav, sidebarSurface } from './Sidebar'
import { cn } from '@/utils/cn'

export const MobileNav = ({ open, onClose, sections, homePath }) => (
  <Dialog open={open} onClose={onClose} className="relative z-50 lg:hidden">
    <div className="fixed inset-0 bg-slate-950/50 backdrop-blur-[2px]" aria-hidden="true" />
    <div className="fixed inset-0 flex">
      <DialogPanel
        className={cn(
          'relative flex w-72 max-w-[85vw] flex-col shadow-2xl animate-slide-up',
          sidebarSurface,
        )}
      >
        <button
          type="button"
          onClick={onClose}
          className="absolute right-3 top-3 z-10 rounded-lg p-1.5 text-white/70 transition hover:bg-white/10 hover:text-white"
          aria-label="Close navigation"
        >
          <XMarkIcon className="h-5 w-5" />
        </button>
        <SidebarNav sections={sections} homePath={homePath} onNavigate={onClose} />
      </DialogPanel>
    </div>
  </Dialog>
)
