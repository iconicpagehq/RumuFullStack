import React, { useState } from 'react'
import { useSocialInbox } from '../hooks/useSocialInbox'
import { ConversationList } from '../components/ConversationList'
import { MessageThread } from '../components/MessageThread'
import { PageHeader } from '@/components/shared/PageHeader'
import { toast } from 'react-hot-toast'

export default function SocialInbox() {
  const [platformFilter, setPlatformFilter] = useState('all')
  const [searchQuery, setSearchQuery] = useState('')

  const initialFilters = {
    platform: platformFilter === 'all' ? undefined : platformFilter,
    search: searchQuery || undefined,
  }

  const {
    conversations,
    isConversationsLoading,
    selectedConversationId,
    setSelectedConversationId,
    currentThread,
    isThreadLoading,
    filters,
    setFilters,
    sendMessage,
    isSending,
    updateConversation,
  } = useSocialInbox(initialFilters)

  const handleFilterChange = (platform) => {
    setPlatformFilter(platform)
    setFilters((prev) => ({
      ...prev,
      platform: platform === 'all' ? undefined : platform,
    }))
  }

  const handleSearchChange = (query) => {
    setSearchQuery(query)
    setFilters((prev) => ({
      ...prev,
      search: query || undefined,
    }))
  }

  const handleSendMessage = async (convId, text) => {
    try {
      await sendMessage({ conversationId: convId, message: text })
    } catch (err) {
      toast.error(err?.response?.data?.message || 'Failed to send message.')
    }
  }

  const handleUpdateNotes = async (convId, data) => {
    try {
      await updateConversation({ conversationId: convId, data })
      toast.success('Internal notes updated.')
    } catch (err) {
      toast.error('Failed to update notes.')
    }
  }

  return (
    <div className="space-y-4 flex flex-col h-[calc(100vh-140px)]">
      <PageHeader
        title="Unified Social Inbox"
        description="Consolidated customer messaging across Facebook Messenger, Instagram Direct, and WhatsApp Business."
      />

      <div className="flex-1 min-h-0 bg-white rounded-xl border border-slate-200 overflow-hidden flex shadow-sm">
        {/* Left pane: Conversation List */}
        <div className="w-80 md:w-96 flex-shrink-0 h-full">
          <ConversationList
            conversations={conversations}
            selectedId={selectedConversationId}
            onSelect={setSelectedConversationId}
            platformFilter={platformFilter}
            onFilterChange={handleFilterChange}
            searchQuery={searchQuery}
            onSearchChange={handleSearchChange}
            isLoading={isConversationsLoading}
          />
        </div>

        {/* Right pane: Message Thread */}
        <div className="flex-1 h-full min-w-0">
          <MessageThread
            conversation={currentThread?.conversation}
            messages={currentThread?.messages || []}
            isLoading={isThreadLoading}
            onSendMessage={handleSendMessage}
            isSending={isSending}
            onUpdateNotes={handleUpdateNotes}
          />
        </div>
      </div>
    </div>
  )
}

