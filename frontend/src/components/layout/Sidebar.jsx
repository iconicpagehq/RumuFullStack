import { useEffect, useState } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import { ChevronDownIcon } from '@heroicons/react/24/outline'
import { Brand } from '@/components/shared/Brand'
import { useNotificationStore } from '@/stores/notificationStore'
import { cn } from '@/utils/cn'

export const sidebarSurface = 'sidebar-surface'

export const SidebarNav = ({ sections, onNavigate, homePath = '/' }) => {
  const location = useLocation()
  const navigate = useNavigate()
  const billingUnread = useNotificationStore((state) => state.billingUnread)
  const unread = useNotificationStore((state) => state.unread)
  const badges = { billingUnread, unread }

  const [expanded, setExpanded] = useState(() => {
    const initial = {}
    sections.forEach((sec) => {
      sec.items.forEach((item) => {
        if (item.children) {
          const isChildActive = item.children.some(
            (c) => location.pathname === c.to || location.pathname.startsWith(c.to),
          )
          if (isChildActive || location.pathname === item.to || location.pathname.startsWith(item.to)) {
            initial[item.label] = true
          }
        }
      })
    })
    return initial
  })

  useEffect(() => {
    sections.forEach((sec) => {
      sec.items.forEach((item) => {
        if (item.children) {
          const isChildActive = item.children.some(
            (c) => location.pathname === c.to || location.pathname.startsWith(c.to),
          )
          if (isChildActive || location.pathname === item.to || location.pathname.startsWith(item.to)) {
            setExpanded((prev) => ({ ...prev, [item.label]: true }))
          }
        }
      })
    })
  }, [location.pathname, sections])

  const toggleExpand = (label, defaultTo) => {
    setExpanded((prev) => {
      const nextState = !prev[label]
      if (nextState && defaultTo) {
        navigate(defaultTo)
        onNavigate?.()
      }
      return { ...prev, [label]: nextState }
    })
  }

  return (
    <div className="flex h-full flex-col">
      <div className="flex h-[76px] shrink-0 items-center border-b border-white/[0.08] px-5">
        <Brand to={homePath} dark logoClassName="h-8" />
      </div>
      <nav className="flex-1 space-y-7 overflow-y-auto px-3 py-6 scrollbar-thin">
        {sections.map((section, sectionIndex) => (
          <div key={section.label || sectionIndex}>
            {section.label && (
              <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/35">
                {section.label}
              </p>
            )}
            <div className="space-y-1">
              {section.items.map((item) => {
                const Icon = item.icon
                const count = item.badgeKey ? badges[item.badgeKey] : 0
                const hasChildren = Boolean(item.children && item.children.length > 0)

                if (hasChildren) {
                  const isExpanded = Boolean(expanded[item.label])
                  const isAnyChildActive = item.children.some(
                    (c) => location.pathname === c.to || location.pathname.startsWith(c.to),
                  )
                  const isParentActive = isAnyChildActive || location.pathname === item.to

                  return (
                    <div key={item.label || item.to} className="space-y-1">
                      <button
                        type="button"
                        onClick={() => toggleExpand(item.label, !isExpanded ? item.children[0]?.to : null)}
                        className={cn(
                          'group relative flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition text-left',
                          isParentActive
                            ? 'bg-white/[0.08] text-white'
                            : 'text-white/55 hover:bg-white/[0.06] hover:text-white',
                        )}
                      >
                        {isParentActive && (
                          <span className="absolute left-0 top-1/2 h-4 w-0.5 -translate-y-1/2 rounded-r-full bg-brand-highlight" />
                        )}
                        {Icon && (
                          <Icon
                            className={cn(
                              'h-[18px] w-[18px] shrink-0 transition-colors',
                              isParentActive ? 'text-brand-highlight' : 'text-white/45 group-hover:text-white/70',
                            )}
                          />
                        )}
                        <span className="min-w-0 flex-1 truncate">{item.label}</span>
                        {count > 0 && (
                          <span className="rounded-full bg-highlight px-1.5 py-0.5 text-[10px] font-semibold leading-none text-[#260720]">
                            {count > 99 ? '99+' : count}
                          </span>
                        )}
                        <ChevronDownIcon
                          className={cn(
                            'h-4 w-4 shrink-0 text-white/40 transition-transform duration-200 group-hover:text-white/70',
                            isExpanded ? 'rotate-0' : '-rotate-90',
                          )}
                        />
                      </button>

                      {isExpanded && (
                        <div className="ml-5 space-y-1 border-l border-white/10 pl-3 pt-0.5">
                          {item.children.map((child) => {
                            const ChildIcon = child.icon
                            return (
                              <NavLink
                                key={child.to}
                                to={child.to}
                                end={child.end}
                                onClick={onNavigate}
                                className={({ isActive }) =>
                                  cn(
                                    'group relative flex items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition',
                                    isActive
                                      ? 'bg-white/[0.08] text-white font-semibold'
                                      : 'text-white/45 hover:bg-white/[0.04] hover:text-white/80',
                                  )
                                }
                              >
                                {({ isActive }) => (
                                  <>
                                    <span
                                      className={cn(
                                        'h-1.5 w-1.5 rounded-full transition-colors shrink-0',
                                        isActive ? 'bg-brand-highlight' : 'bg-white/20 group-hover:bg-white/40',
                                      )}
                                    />
                                    {ChildIcon && (
                                      <ChildIcon
                                        className={cn(
                                          'h-3.5 w-3.5 shrink-0 transition-colors',
                                          isActive ? 'text-brand-highlight' : 'text-white/40 group-hover:text-white/60',
                                        )}
                                      />
                                    )}
                                    <span className="truncate">{child.label}</span>
                                  </>
                                )}
                              </NavLink>
                            )
                          })}
                        </div>
                      )}
                    </div>
                  )
                }

                return (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    end={item.end}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        'group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        isActive
                          ? 'bg-white/[0.08] text-white'
                          : 'text-white/55 hover:bg-white/[0.06] hover:text-white',
                      )
                    }
                  >
                    {({ isActive }) => (
                      <>
                        {isActive && (
                          <span className="absolute left-0 top-1/2 h-4 w-0.5 -translate-y-1/2 rounded-r-full bg-brand-highlight" />
                        )}
                        {Icon && (
                          <Icon
                            className={cn(
                              'h-[18px] w-[18px] shrink-0 transition-colors',
                              isActive ? 'text-brand-highlight' : 'text-white/45 group-hover:text-white/70',
                            )}
                          />
                        )}
                        <span className="min-w-0 flex-1 truncate">{item.label}</span>
                        {count > 0 && (
                          <span className="rounded-full bg-highlight px-1.5 py-0.5 text-[10px] font-semibold leading-none text-[#260720]">
                            {count > 99 ? '99+' : count}
                          </span>
                        )}
                      </>
                    )}
                  </NavLink>
                )
              })}
            </div>
          </div>
        ))}
      </nav>
    </div>
  )
}

export const Sidebar = ({ sections, homePath }) => (
  <aside
    className={cn(
      'fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-black/20 lg:block',
      sidebarSurface,
    )}
  >
    <SidebarNav sections={sections} homePath={homePath} />
  </aside>
)
