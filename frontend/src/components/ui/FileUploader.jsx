import { useRef, useState } from 'react'
import { ArrowUpTrayIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/react/24/outline'
import { formatFileSize } from '@/utils/formatters'
import { cn } from '@/utils/cn'

export const FileUploader = ({
  file,
  onFileChange,
  accept = '.csv,text/csv',
  hint,
  error,
  disabled = false,
  className,
}) => {
  const inputRef = useRef(null)
  const [dragging, setDragging] = useState(false)

  const pick = (files) => {
    const next = files?.[0]
    if (next) onFileChange(next)
  }

  const openPicker = () => {
    if (!disabled) inputRef.current?.click()
  }

  return (
    <div className={className}>
      <div
        role="button"
        tabIndex={disabled ? -1 : 0}
        onClick={openPicker}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault()
            openPicker()
          }
        }}
        onDragOver={(event) => {
          event.preventDefault()
          if (!disabled) setDragging(true)
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={(event) => {
          event.preventDefault()
          setDragging(false)
          if (!disabled) pick(event.dataTransfer.files)
        }}
        className={cn(
          'flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed px-6 py-8 text-center transition',
          dragging ? 'border-highlight bg-primary/5' : 'border-border bg-muted/30 hover:border-highlight/40 hover:bg-muted/50',
          disabled && 'cursor-not-allowed opacity-60',
          error && 'border-destructive',
        )}
      >
        <ArrowUpTrayIcon className="h-7 w-7 text-muted-foreground" />
        <p className="mt-3 text-sm font-medium text-foreground">
          <span className="text-highlight">Choose a file</span> or drag it here
        </p>
        {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
      </div>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        disabled={disabled}
        onChange={(event) => {
          pick(event.target.files)
          event.target.value = ''
        }}
      />
      {file && (
        <div className="mt-3 flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-2.5">
          <DocumentTextIcon className="h-5 w-5 shrink-0 text-highlight" />
          <div className="min-w-0 flex-1">
            <p className="truncate text-sm font-medium text-foreground">{file.name}</p>
            <p className="text-xs text-muted-foreground">{formatFileSize(file.size)}</p>
          </div>
          <button
            type="button"
            onClick={() => onFileChange(null)}
            className="rounded p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            aria-label="Remove file"
          >
            <XMarkIcon className="h-4 w-4" />
          </button>
        </div>
      )}
      {error && <p className="form-error">{error}</p>}
    </div>
  )
}
