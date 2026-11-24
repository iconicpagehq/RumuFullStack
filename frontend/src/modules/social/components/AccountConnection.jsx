import React, { useState } from 'react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { socialService } from '../services/socialService'
import { toast } from 'react-hot-toast'
import { ShieldCheckIcon, LinkIcon } from '@heroicons/react/24/outline'

export function AccountConnection({ onConnected }) {
  const [isConnecting, setIsConnecting] = useState(false)

  const handleConnect = async () => {
    setIsConnecting(true)
    try {
      // Use the exact whitelisted HTTPS ngrok endpoint when developing on localhost
      const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
      const redirectUri = isLocal
        ? 'https://saxophone-unscented-tall.ngrok-free.dev/connect/facebook/callback'
        : `${window.location.origin}/realtor/social/accounts`

      const resp = await socialService.getConnectUrl(redirectUri)

      if (resp.data?.authorization_url) {
        // Redirect to Meta OAuth Dialog
        window.location.href = resp.data.authorization_url
      } else {
        toast.error('Unable to initialize Meta OAuth.')
      }
    } catch (err) {
      toast.error(err?.response?.data?.message || 'Failed to start authorization flow.')
    } finally {
      setIsConnecting(false)
    }
  }

  return (
    <Card className="p-6 bg-gradient-to-r from-blue-50/50 via-indigo-50/30 to-purple-50/50 border border-blue-100">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <div className="flex items-center space-x-2">
            <span className="p-2 rounded-lg bg-blue-600 text-white">
              <LinkIcon className="w-5 h-5" />
            </span>
            <h3 className="font-bold text-lg text-slate-900">Connect Meta Accounts (Facebook, Instagram & WhatsApp)</h3>
          </div>
          <p className="text-sm text-slate-600 max-w-2xl">
            Authorize RUMU CRM with your Meta account to manage Facebook Pages, Instagram Professional accounts,
            WhatsApp Business, Messenger, Direct Messages, comments, and performance analytics from a single unified workspace.
          </p>

          <div className="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-1">
            <div className="flex items-center space-x-1">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-600" />
              <span>Multi-tenant encrypted tokens</span>
            </div>
            <div className="flex items-center space-x-1">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-600" />
              <span>Messenger, Instagram & WhatsApp</span>
            </div>
            <div className="flex items-center space-x-1">
              <ShieldCheckIcon className="w-4 h-4 text-emerald-600" />
              <span>Real-time webhook sync</span>
            </div>
          </div>
        </div>

        <div className="flex-shrink-0">
          <Button
            size="lg"
            onClick={handleConnect}
            disabled={isConnecting}
            className="bg-[#1877F2] hover:bg-[#166fe5] text-white font-medium shadow-sm"
          >
            {isConnecting ? 'Connecting...' : 'Connect with Meta'}
          </Button>
        </div>
      </div>
    </Card>
  )
}
