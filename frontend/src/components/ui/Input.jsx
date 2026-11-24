import { forwardRef } from 'react'
import { ChevronDownIcon } from '@heroicons/react/20/solid'
import { cn } from '@/utils/cn'

export const FormField = ({ label, htmlFor, hint, error, required, className, children }) => (
  <div className={cn('w-full', className)}>
    {label && (
      <label htmlFor={htmlFor} className="form-label">
        {label}
        {required && <span className="ml-0.5 text-destructive">*</span>}
      </label>
    )}
    {children}
    {error ? <p className="form-error">{error}</p> : hint ? <p className="form-hint">{hint}</p> : null}
  </div>
)

export const Input = forwardRef(function Input(
  { className, invalid = false, leftIcon: LeftIcon, rightSlot, ...props },
  ref,
) {
  const field = (
    <input
      ref={ref}
      className={cn(
        'input-base',
        LeftIcon && 'pl-10',
        rightSlot && 'pr-10',
        invalid && 'border-destructive focus:border-destructive focus:ring-destructive/20',
        className,
      )}
      {...props}
    />
  )

  if (!LeftIcon && !rightSlot) return field

  return (
    <div className="relative">
      {LeftIcon && (
        <LeftIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
      )}
      {field}
      {rightSlot && <div className="absolute right-2 top-1/2 -translate-y-1/2">{rightSlot}</div>}
    </div>
  )
})

export const Textarea = forwardRef(function Textarea({ className, invalid = false, rows = 4, ...props }, ref) {
  return (
    <textarea
      ref={ref}
      rows={rows}
      className={cn(
        'input-base h-auto min-h-[96px] resize-y py-2.5',
        invalid && 'border-destructive focus:border-destructive focus:ring-destructive/20',
        className,
      )}
      {...props}
    />
  )
})

export const Select = forwardRef(function Select(
  { className, invalid = false, children, ...props },
  ref,
) {
  return (
    <div className="relative">
      <select
        ref={ref}
        className={cn(
          'input-base appearance-none pr-9',
          invalid && 'border-destructive focus:border-destructive focus:ring-destructive/20',
          className,
        )}
        {...props}
      >
        {children}
      </select>
      <ChevronDownIcon className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
    </div>
  )
})
