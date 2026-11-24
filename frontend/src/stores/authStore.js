import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export const useAuthStore = create(
  persist(
    (set) => ({
      token: null,
      user: null,
      setAuth: ({ token, user }) => set({ token, user }),
      setUser: (user) => set({ user }),
      clear: () => set({ token: null, user: null }),
    }),
    {
      name: 'rumu-auth',
      partialize: (state) => ({ token: state.token, user: state.user }),
    },
  ),
)

export const getAuthToken = () => useAuthStore.getState().token
