import React, { useEffect, useState, useRef } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useSocialAccounts } from '../hooks/useSocialAccounts'
import { AccountCard } from '../components/AccountCard'
import { AccountConnection } from '../components/AccountConnection'
import { PageHeader } from '@/components/shared/PageHeader'
import { socialService } from '../services/socialService'
import { toast } from 'react-hot-toast'

export default function SocialAccounts({ hideHeader = false }) {
  const [searchParams, setSearchParams] = useSearchParams()
  const { accounts, isLoading, isError, disconnect, isDisconnecting, sync, isSyncing, refetch } =
    useSocialAccounts()
  const [isProcessingCallback, setIsProcessingCallback] = useState(false)
  const callbackExecutedRef = useRef(false)

  // Handle Meta OAuth callback if returning with code & state
  useEffect(() => {
    const code = searchParams.get('code')
    const state = searchParams.get('state')

    if (code && state && !callbackExecutedRef.current) {
      callbackExecutedRef.current = true
      setIsProcessingCallback(true)
      socialService
        .handleCallback(code, state)
        .then(() => {
          toast.success('Meta accounts and pages connected successfully!')
          refetch()
        })
        .catch((err) => {
          toast.error(err?.response?.data?.message || 'Failed to complete Meta authorization.')
        })
        .finally(() => {
          setIsProcessingCallback(false)
          // Clean query params from URL
          searchParams.delete('code')
          searchParams.delete('state')
          setSearchParams(searchParams, { replace: true })
        })
    }
  }, [searchParams])

  const handleDisconnect = async (id) => {
    if (window.confirm('Are you sure you want to disconnect this account and deactivate its pages?')) {
      try {
        await disconnect(id)
        toast.success('Account disconnected.')
      } catch (err) {
        toast.error('Failed to disconnect account.')
      }
    }
  }

  const handleSync = async (id) => {
    try {
      await sync(id)
      toast.success('Synchronization job dispatched.')
    } catch (err) {
      toast.error('Failed to start sync.')
    }
  }

  return (
    <div className="space-y-6">
      {!hideHeader && (
        <PageHeader
          title="Connected Social Accounts"
          description="Manage connected Meta Developer App accounts, Facebook Pages, Instagram Business accounts, and WhatsApp numbers."
        />
      )}

      {isProcessingCallback && (
        <div className="p-4 rounded-xl bg-blue-50 text-blue-700 text-sm font-medium animate-pulse border border-blue-200">
          Completing Meta authorization and exchanging tokens...
        </div>
      )}

      {/* Connect Banner */}
      <AccountConnection />

      {/* Connected Accounts List */}
      <div className="space-y-4">
        <h3 className="font-semibold text-slate-900 text-base">Authorized Connections</h3>

        {isLoading ? (
          <div className="p-8 text-center text-sm text-slate-400 bg-white rounded-xl border border-slate-200">
            Loading connected accounts...
          </div>
        ) : accounts.length === 0 ? (
          <div className="p-12 text-center text-sm text-slate-500 bg-white rounded-xl border border-dashed border-slate-300">
            <p className="font-medium text-slate-700">No social accounts connected yet</p>
            <p className="text-xs text-slate-400 mt-1">
              Click &quot;Connect with Meta&quot; above to link your Facebook Pages and Instagram Business accounts.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4">
            {accounts.map((account) => (
              <AccountCard
                key={account.id}
                account={account}
                onDisconnect={handleDisconnect}
                onSync={handleSync}
                isSyncing={isSyncing}
                isDisconnecting={isDisconnecting}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

