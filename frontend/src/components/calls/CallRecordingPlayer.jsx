import { useEffect, useState } from 'react'
import { ExclamationTriangleIcon, MusicalNoteIcon } from '@heroicons/react/24/outline'
import { Button } from '@/components/ui/Button'
import { getErrorMessage } from '@/utils/errors'

export const CallRecordingPlayer = ({ api, call, autoPlay = false, className }) => {
  const [src, setSrc] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const available = Boolean(call?.recording?.available)
  const pending = Boolean(call?.recording?.pending)

  useEffect(() => {
    let objectUrl = null
    let cancelled = false

    setSrc(null)
    setError('')

    if (!available) return undefined

    setLoading(true)
    api
      .recordingBlob(call.id)
      .then((response) => {
        if (cancelled) return
        objectUrl = URL.createObjectURL(response.data)
        setSrc(objectUrl)
      })
      .catch((err) => {
        if (cancelled) return
        setError(getErrorMessage(err, 'Could not load this recording.'))
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [api, call?.id, available])

  if (!available && !pending) {
    return (
      <p className={className || 'text-xs text-muted-foreground'}>
        No recording was stored for this call.
      </p>
    )
  }

  if (pending) {
    return (
      <p className={className || 'text-xs text-muted-foreground'}>
        The recording is being prepared. Refresh in a minute to play it.
      </p>
    )
  }

  if (loading) {
    return <div className="skeleton h-11 w-full rounded-lg" />
  }

  if (error) {
    return (
      <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/5 px-3 py-2 text-xs text-destructive">
        <ExclamationTriangleIcon className="h-4 w-4 shrink-0" />
        {error}
      </div>
    )
  }

  return (
    <div className={className}>
      <div className="mb-2 flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground">
        <MusicalNoteIcon className="h-3.5 w-3.5" />
        Call recording
      </div>
      <audio controls preload="metadata" autoPlay={autoPlay} src={src} className="h-10 w-full" />
    </div>
  )
}

export const RecordingPlayButton = ({ api, call, label = 'Play recording' }) => {
  const [open, setOpen] = useState(false)

  return (
    <div>
      <Button
        size="sm"
        variant="ghost"
        onClick={(event) => {
          event.stopPropagation()
          setOpen((value) => !value)
        }}
      >
        <MusicalNoteIcon className="h-4 w-4" />
        {open ? 'Hide recording' : label}
      </Button>
      {open && (
        <div onClick={(event) => event.stopPropagation()} className="mt-2">
          <CallRecordingPlayer api={api} call={call} autoPlay />
        </div>
      )}
    </div>
  )
}
