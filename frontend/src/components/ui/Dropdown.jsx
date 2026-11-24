import { Fragment } from 'react'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react'
import { Link } from 'react-router-dom'
import { cn } from '@/utils/cn'

export const Dropdown = ({ trigger, children, align = 'right', className }) => (
  <Menu as="div" className={cn('relative inline-block text-left', className)}>
    <MenuButton as={Fragment}>{trigger}</MenuButton>
    <MenuItems
      transition
      className={cn(
        'absolute z-40 mt-2 w-56 origin-top rounded-xl border border-border bg-card p-1.5 shadow-card transition focus:outline-none data-[closed]:scale-95 data-[closed]:opacity-0',
        align === 'right' ? 'right-0' : 'left-0',
      )}
    >
      {children}
    </MenuItems>
  </Menu>
)

export const DropdownItem = ({ children, icon: Icon, onClick, to, danger = false, disabled = false }) => {
  const classes = ({ focus }) =>
    cn(
      'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition',
      danger ? 'text-destructive' : 'text-foreground',
      focus && !disabled && 'bg-muted',
      disabled && 'cursor-not-allowed opacity-50',
    )

  return (
    <MenuItem disabled={disabled}>
      {(state) =>
        to ? (
          <Link to={to} className={classes(state)} onClick={onClick}>
            {Icon && <Icon className="h-4 w-4 shrink-0" />}
            {children}
          </Link>
        ) : (
          <button type="button" onClick={onClick} className={classes(state)} disabled={disabled}>
            {Icon && <Icon className="h-4 w-4 shrink-0" />}
            {children}
          </button>
        )
      }
    </MenuItem>
  )
}

export const DropdownLabel = ({ children }) => (
  <div className="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
    {children}
  </div>
)

export const DropdownDivider = () => <div className="-mx-1 my-1 h-px bg-border" />
