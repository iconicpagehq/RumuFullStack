import React from 'react'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ArrowPathIcon, TrashIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline'

export function AccountCard({ account, onSync, onDisconnect, isSyncing, isDisconnecting }) {
  const isConnected = account.status === 'connected'
  const expiresAt = account.token_expires_at ? new Date(account.token_expires_at).toLocaleDateString() : 'Long-lived'
  const lastSynced = account.last_synced_at ? new Date(account.last_synced_at).toLocaleString() : 'Never'

  return (
    <Card className="p-6 transition hover:shadow-md border border-slate-200">
      <div className="flex items-start justify-between">
        <div className="flex items-center space-x-4">
          <div className="relative">
            {account.profile_picture_url ? (
              <img
                src={account.profile_picture_url}
                alt={account.name}
                className="w-14 h-14 rounded-full object-cover border border-slate-200"
              />
            ) : (
              <div className="w-14 h-14 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-xl">
                {account.name ? account.name[0] : 'M'}
              </div>
            )}
            <span
              className={`absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center ${
                isConnected ? 'bg-emerald-500' : 'bg-amber-500'
              }`}
            >
              {isConnected ? (
                <CheckCircleIcon className="w-3.5 h-3.5 text-white" />
              ) : (
                <ExclamationTriangleIcon className="w-3.5 h-3.5 text-white" />
              )}
            </span>
          </div>

          <div>
            <div className="flex items-center space-x-2">
              <h3 className="font-semibold text-lg text-slate-900">{account.name || 'Meta Account'}</h3>
              <Badge variant={isConnected ? 'success' : 'warning'} className="capitalize">
                {account.status}
              </Badge>
            </div>
            <p className="text-sm text-slate-500">
              {account.username ? `@${account.username}` : `ID: ${account.external_id}`}
            </p>
            <div className="mt-1 flex items-center space-x-3 text-xs text-slate-400">
              <span>Expires: {expiresAt}</span>
              <span>•</span>
              <span>Last synced: {lastSynced}</span>
            </div>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => onSync(account.id)}
            disabled={isSyncing || !isConnected}
            title="Synchronize data"
          >
            <ArrowPathIcon className={`w-4 h-4 mr-1 ${isSyncing ? 'animate-spin' : ''}`} />
            Sync
          </Button>

          <Button
            variant="ghost"
            size="sm"
            onClick={() => onDisconnect(account.id)}
            disabled={isDisconnecting}
            className="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
            title="Disconnect account"
          >
            <TrashIcon className="w-4 h-4 mr-1" />
            Disconnect
          </Button>
        </div>
      </div>

      {/* Pages and linked accounts list */}
      {account.pages && account.pages.length > 0 && (
        <div className="mt-5 pt-4 border-t border-slate-100">
          <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
            Connected Pages & Profiles ({account.pages.length})
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
            {account.pages.map((page) => (
              <div
                key={page.id}
                className="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-sm"
              >
                <div className="flex items-center space-x-2">
                  <span
                    className={`px-2 py-0.5 rounded text-xs font-semibold ${
                      page.platform === 'whatsapp'
                        ? 'bg-emerald-100 text-emerald-700'
                        : page.platform === 'instagram'
                        ? 'bg-pink-100 text-pink-700'
                        : 'bg-blue-100 text-blue-700'
                    }`}
                  >
                    {page.platform === 'whatsapp' ? 'WhatsApp' : page.platform === 'instagram' ? 'Instagram' : 'Facebook'}
                  </span>
                  <span className="font-medium text-slate-800 truncate max-w-[140px]">{page.name}</span>
                </div>
                <Badge variant={page.is_active ? 'success' : 'default'} className="text-[10px]">
                  {page.is_active ? 'Active' : 'Inactive'}
                </Badge>
              </div>
            ))}
          </div>
        </div>
      )}
    </Card>
  )
}

