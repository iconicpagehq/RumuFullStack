import { useState } from 'react'
import { Outlet } from 'react-router-dom'
import { Sidebar } from './Sidebar'
import { MobileNav } from './MobileNav'
import { Topbar } from './Topbar'
import { Footer } from './Footer'

export const DashboardLayout = ({ sections, homePath, banner }) => {
  const [mobileOpen, setMobileOpen] = useState(false)

  return (
    <div className="min-h-screen bg-background flex flex-col">
      <Sidebar sections={sections} homePath={homePath} />
      <MobileNav
        open={mobileOpen}
        onClose={() => setMobileOpen(false)}
        sections={sections}
        homePath={homePath}
      />
      <div className="lg:pl-64 flex flex-1 flex-col">
        <Topbar onMenuClick={() => setMobileOpen(true)} />
        <main className="mx-auto w-full max-w-[1500px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
          {banner}
          <Outlet />
        </main>
        <div className="mx-auto w-full max-w-[1500px] px-4 sm:px-6 lg:px-8">
          <Footer />
        </div>
      </div>
    </div>
  )
}
