import React from 'react'
import { Badge } from '@/components/ui/Badge'
import { ChatBubbleLeftRightIcon } from '@heroicons/react/24/outline'

export function ConversationList({
  conversations = [],
  selectedId,
  onSelect,
  platformFilter,
  onFilterChange,
  searchQuery,
  onSearchChange,
  isLoading,
}) {
  return (
    <div className="flex flex-col h-full bg-white border-r border-slate-200">
      {/* Header & Filters */}
      <div className="p-4 border-b border-slate-200 space-y-3">
        <div className="flex items-center justify-between">
          <h3 className="font-semibold text-slate-900 flex items-center space-x-2">
            <ChatBubbleLeftRightIcon className="w-5 h-5 text-primary-600" />
            <span>Messages</span>
          </h3>
          <span className="text-xs text-slate-500 font-medium">
            {conversations.length} {conversations.length === 1 ? 'chat' : 'chats'}
          </span>
        </div>

        {/* Search */}
        <input
          type="text"
          placeholder="Search conversations..."
          value={searchQuery}
          onChange={(e) => onSearchChange(e.target.value)}
          className="w-full text-xs px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary-500"
        />

        {/* Platform tabs */}
        <div className="flex space-x-1 p-1 bg-slate-100 rounded-lg text-xs">
          <button
            onClick={() => onFilterChange('all')}
            className={`flex-1 py-1 rounded-md font-medium transition ${
              platformFilter === 'all' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            All
          </button>
          <button
            onClick={() => onFilterChange('facebook')}
            className={`flex-1 py-1 rounded-md font-medium transition ${
              platformFilter === 'facebook' ? 'bg-white shadow-sm text-blue-600' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Messenger
          </button>
          <button
            onClick={() => onFilterChange('instagram')}
            className={`flex-1 py-1 rounded-md font-medium transition ${
              platformFilter === 'instagram' ? 'bg-white shadow-sm text-pink-600' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Instagram
          </button>
          <button
            onClick={() => onFilterChange('whatsapp')}
            className={`flex-1 py-1 rounded-md font-medium transition ${
              platformFilter === 'whatsapp' ? 'bg-white shadow-sm text-emerald-600' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            WhatsApp
          </button>
        </div>
      </div>

      {/* Conversation Thread Items */}
      <div className="flex-1 overflow-y-auto divide-y divide-slate-100">
        {isLoading ? (
          <div className="p-3 space-y-2">
            {[1, 2, 3, 4].map((n) => (
              <div key={n} className="flex items-center space-x-3 p-2 animate-pulse rounded-lg bg-slate-50">
                <div className="w-10 h-10 rounded-full bg-slate-200 shrink-0" />
                <div className="flex-1 min-w-0 space-y-2">
                  <div className="h-3 bg-slate-200 rounded w-1/2" />
                  <div className="h-2.5 bg-slate-100 rounded w-4/5" />
                </div>
              </div>
            ))}
          </div>
        ) : conversations.length === 0 ? (
          <div className="p-6 text-center space-y-2">
            <ChatBubbleLeftRightIcon className="w-8 h-8 text-slate-300 mx-auto" />
            <p className="text-xs font-semibold text-slate-700">No conversations found</p>
            <p className="text-[11px] text-slate-400 leading-relaxed">
              Unified incoming customer chats from Facebook Messenger, Instagram Direct, and WhatsApp Business Cloud API.
            </p>
          </div>
        ) : (
          conversations.map((conv) => {
            const isSelected = selectedId === conv.id
            const isUnread = conv.unread_count > 0
            const isInstagram = conv.platform === 'instagram'
            const isWhatsApp = conv.platform === 'whatsapp'

            return (
              <div
                key={conv.id}
                onClick={() => onSelect(conv.id)}
                className={`p-3.5 cursor-pointer transition flex items-start space-x-3 ${
                  isSelected ? 'bg-primary-50/70 border-l-4 border-primary-600' : 'hover:bg-slate-50'
                }`}
              >
                <div className="relative flex-shrink-0">
                  {conv.participant_avatar ? (
                    <img
                      src={conv.participant_avatar}
                      alt={conv.participant_name || 'User'}
                      className="w-10 h-10 rounded-full object-cover border border-slate-200"
                    />
                  ) : (
                    <div
                      className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm ${
                        isWhatsApp
                          ? 'bg-emerald-100 text-emerald-700'
                          : isInstagram
                          ? 'bg-pink-100 text-pink-700'
                          : 'bg-blue-100 text-blue-700'
                      }`}
                    >
                      {((conv.participant_name || conv.participant_username || 'U').replace(/^@/, '')[0] || 'U').toUpperCase()}
                    </div>
                  )}
                  <span
                    className={`absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border border-white text-[8px] flex items-center justify-center text-white font-bold ${
                      isWhatsApp ? 'bg-emerald-600' : isInstagram ? 'bg-pink-600' : 'bg-blue-600'
                    }`}
                    title={isWhatsApp ? 'WhatsApp' : isInstagram ? 'Instagram Direct' : 'Facebook Messenger'}
                  >
                    {isWhatsApp ? 'WA' : isInstagram ? 'IG' : 'FB'}
                  </span>
                </div>

                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between">
                    <h4
                      className={`text-sm truncate ${
                        isUnread ? 'font-bold text-slate-900' : 'font-medium text-slate-700'
                      }`}
                    >
                      {conv.participant_name || conv.participant_username || 'Participant'}
                    </h4>
                    {conv.last_message_at && (
                      <span className="text-[10px] text-slate-400 ml-1 flex-shrink-0">
                        {new Date(conv.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                      </span>
                    )}
                  </div>

                  <p
                    className={`text-xs truncate mt-0.5 ${
                      isUnread ? 'font-semibold text-slate-800' : 'text-slate-500'
                    }`}
                  >
                    {conv.last_message_text || 'No message history'}
                  </p>

                  <div className="mt-1 flex items-center justify-between">
                    <span className="text-[10px] text-slate-400 truncate max-w-[120px]">
                      {conv.page?.name || 'Page'}
                    </span>
                    {isUnread && (
                      <span className="px-1.5 py-0.2 rounded-full bg-primary-600 text-white text-[10px] font-bold">
                        {conv.unread_count}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            )
          })
        )}
      </div>
    </div>
  )
}

