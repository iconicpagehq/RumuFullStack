import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'react-hot-toast'
import App from './App'
import { useThemeStore, watchSystemTheme } from './stores/themeStore'
import './index.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: (failureCount, error) => {
        const status = error?.response?.status
        if (status && status < 500) return false
        return failureCount < 1
      },
      staleTime: 30000,
    },
  },
})

useThemeStore.getState().syncTheme()
watchSystemTheme()

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <App />
        <Toaster
          position="top-right"
          gutter={8}
          toastOptions={{
            duration: 4000,
            style: {
              background: 'rgb(var(--card))',
              color: 'rgb(var(--foreground))',
              border: '1px solid rgb(var(--border))',
              boxShadow: '0 12px 32px -16px rgba(16, 24, 40, 0.3)',
              fontSize: '14px',
              fontWeight: 500,
            },
            success: {
              iconTheme: { primary: '#059669', secondary: '#ecfdf5' },
            },
            error: {
              iconTheme: { primary: '#dc2626', secondary: '#fef2f2' },
            },
          }}
        />
      </BrowserRouter>
    </QueryClientProvider>
  </React.StrictMode>,
)
