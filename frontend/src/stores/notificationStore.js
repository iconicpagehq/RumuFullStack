import { create } from 'zustand'

export const useNotificationStore = create((set) => ({
  unread: 0,
  billingUnread: 0,
  setUnread: (unread) => set({ unread }),
  setBillingUnread: (billingUnread) => set({ billingUnread }),
  decrementUnread: () => set((state) => ({ unread: Math.max(0, state.unread - 1) })),
  decrementBillingUnread: () => set((state) => ({ billingUnread: Math.max(0, state.billingUnread - 1) })),
  reset: () => set({ unread: 0, billingUnread: 0 }),
}))
