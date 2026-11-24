import React, { useState, useEffect, useRef } from 'react'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { PaperAirplaneIcon, UserCircleIcon, DocumentTextIcon } from '@heroicons/react/24/outline'

export function MessageThread({
  conversation,
  messages = [],
  isLoading,
  onSendMessage,
  isSending,
  onUpdateNotes,
}) {
  const [inputText, setInputText] = useState('')
  const [notes, setNotes] = useState(conversation?.internal_notes || '')
  const [showNotes, setShowNotes] = useState(false)
  const messagesEndRef = useRef(null)

  useEffect(() => {
    setNotes(conversation?.internal_notes || '')
  }, [conversation?.id, conversation?.internal_notes])

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  const handleSend = async (e) => {
    e.preventDefault()
    if (!inputText.trim() || isSending) return

    const msg = inputText
    setInputText('')
    await onSendMessage(conversation.id, msg)

    if (isWhatsApp) {
      const cleanPhone = (conversation.participant_id || '').replace(/[^0-9]/g, '')
      if (cleanPhone) {
        window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`, '_blank')
      }
    }
  }

  const handleSaveNotes = () => {
    if (onUpdateNotes) {
      onUpdateNotes(conversation.id, { internal_notes: notes })
    }
  }

  if (!conversation) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-8 bg-slate-50 text-slate-400 text-sm">
        <UserCircleIcon className="w-12 h-12 stroke-1 mb-2 text-slate-300" />
        <p>Select a conversation from the left to view messages</p>
      </div>
    )
  }

  const isInstagram = conversation.platform === 'instagram'
  const isWhatsApp = conversation.platform === 'whatsapp'

  return (
    <div className="flex-1 flex flex-col h-full bg-slate-50">
      {/* Thread Header */}
      <div className="p-4 bg-white border-b border-slate-200 flex items-center justify-between">
        <div className="flex items-center space-x-3">
          {conversation.participant_avatar ? (
            <img
              src={conversation.participant_avatar}
              alt={conversation.participant_name || 'User'}
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
              {((conversation.participant_name || conversation.participant_username || 'U')[0]).toUpperCase()}
            </div>
          )}
          <div>
            <div className="flex items-center space-x-2">
              <h3 className="font-semibold text-slate-900">
                {conversation.participant_name || conversation.participant_username || 'Participant'}
              </h3>
              {conversation.participant_username && (
                <span className="text-xs text-slate-400">@{conversation.participant_username}</span>
              )}
              <Badge
                variant={isWhatsApp ? 'success' : isInstagram ? 'default' : 'primary'}
                className={`text-[10px] ${isWhatsApp ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ''}`}
              >
                {isWhatsApp ? 'WhatsApp Business' : isInstagram ? 'Instagram Direct' : 'Facebook Messenger'}
              </Badge>
            </div>
            <p className="text-xs text-slate-500">
              Channel: {conversation.page?.name || (isWhatsApp ? 'WhatsApp' : 'Social Page')} • {isWhatsApp ? 'Phone' : 'ID'}: {conversation.participant_id}
            </p>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          {isWhatsApp && conversation.participant_id && (
            <a
              href={`https://wa.me/${conversation.participant_id.replace(/[^0-9]/g, '')}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center text-xs px-2.5 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-medium transition"
              title="Open chat in WhatsApp Web or App"
            >
              <svg className="w-4 h-4 mr-1 fill-emerald-600" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.303-.058.116-.087.188-.173.289l-.26.302c-.087.086-.177.18-.076.353.101.173.45 1.042 1.309 1.705.586.452.998.601 1.157.674.159.072.253.058.347-.043.094-.101.405-.477.513-.64.108-.164.217-.135.365-.079.148.058.94.443 1.102.524.162.081.271.121.311.189.039.068.039.794-.105 1.199z"/>
              </svg>
              Chat on WhatsApp
            </a>
          )}
          <Button
            variant="outline"
            size="sm"
            onClick={() => setShowNotes(!showNotes)}
            className="text-xs"
          >
            <DocumentTextIcon className="w-4 h-4 mr-1" />
            {showNotes ? 'Hide Notes' : 'Internal Notes'}
          </Button>
        </div>
      </div>

      {/* Internal notes banner */}
      {showNotes && (
        <div className="p-3 bg-amber-50/70 border-b border-amber-200 flex items-center space-x-2">
          <input
            type="text"
            placeholder="Add internal notes for team members..."
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="flex-1 text-xs px-3 py-1.5 border border-amber-200 rounded-md bg-white focus:outline-none"
          />
          <Button size="sm" variant="outline" onClick={handleSaveNotes} className="text-xs bg-white">
            Save Note
          </Button>
        </div>
      )}

      {/* Messages Bubble History */}
      <div className="flex-1 p-4 overflow-y-auto space-y-3">
        {isLoading ? (
          <div className="text-center text-xs text-slate-400 py-8">Loading message history...</div>
        ) : messages.length === 0 ? (
          <div className="text-center text-xs text-slate-400 py-8">
            No messages recorded yet in this conversation.
          </div>
        ) : (
          messages.map((msg) => {
            const isFromPage = msg.is_from_page
            const time = msg.sent_at
              ? new Date(msg.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
              : ''

            return (
              <div
                key={msg.id}
                className={`flex flex-col ${isFromPage ? 'items-end' : 'items-start'}`}
              >
                <div
                  className={`max-w-[70%] rounded-2xl px-4 py-2.5 text-sm shadow-sm ${
                    isFromPage
                      ? isWhatsApp
                        ? 'bg-emerald-600 text-white rounded-br-none'
                        : isInstagram
                        ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-br-none'
                        : 'bg-blue-600 text-white rounded-br-none'
                      : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-none'
                  }`}
                >
                  <p className="whitespace-pre-wrap break-words">{msg.message_text}</p>
                </div>
                <span className="text-[10px] text-slate-400 mt-1 px-1">{time}</span>
              </div>
            )
          })
        )}
        <div ref={messagesEndRef} />
      </div>

      {/* Message Input Form */}
      <div className="bg-white border-t border-slate-200 p-3">
        <form onSubmit={handleSend} className="flex items-center space-x-2">
          <input
            type="text"
            placeholder={`Reply to ${conversation.participant_name || 'customer'} via ${isWhatsApp ? 'WhatsApp' : isInstagram ? 'Instagram' : 'Messenger'}...`}
            value={inputText}
            onChange={(e) => setInputText(e.target.value)}
            disabled={isSending}
            className="flex-1 text-sm px-4 py-2.5 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 bg-slate-50 focus:bg-white"
          />
          <Button
            type="submit"
            disabled={!inputText.trim() || isSending}
            className={`${
              isWhatsApp
                ? 'bg-emerald-600 hover:bg-emerald-700'
                : isInstagram
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:opacity-95'
                : 'bg-blue-600 hover:bg-blue-700'
            } text-white px-4 py-2.5 rounded-xl`}
          >
            <PaperAirplaneIcon className="w-4 h-4 mr-1.5" />
            {isSending ? 'Sending...' : 'Send'}
          </Button>
        </form>
        {isWhatsApp && (
          <p className="text-[11px] text-slate-400 mt-1.5 px-1 flex items-center justify-between">
            <span>✨ Outgoing replies sync to Rumu and open in WhatsApp so the customer receives them immediately.</span>
          </p>
        )}
      </div>
    </div>
  )
}

