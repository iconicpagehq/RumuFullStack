import { create } from 'zustand'
import { persist } from 'zustand/middleware'

const applyTheme = (theme) => {
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'system' && prefersDark))
}

export const useThemeStore = create(
  persist(
    (set, get) => ({
      theme: 'light',
      setTheme: (theme) => {
        applyTheme(theme)
        set({ theme })
      },
      syncTheme: () => applyTheme(get().theme),
    }),
    { name: 'rumu-theme' },
  ),
)

export const watchSystemTheme = () => {
  const media = window.matchMedia('(prefers-color-scheme: dark)')
  const listener = () => {
    if (useThemeStore.getState().theme === 'system') {
      applyTheme('system')
    }
  }
  media.addEventListener('change', listener)
  return () => media.removeEventListener('change', listener)
}
