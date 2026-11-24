import { useEffect, useState } from 'react'
import { assetUrl } from '@/api/axios'
import { initials } from '@/utils/formatters'
import { cn } from '@/utils/cn'

const sizes = {
  xs: 'h-6 w-6 text-[10px]',
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-14 w-14 text-lg',
  xl: 'h-20 w-20 text-2xl',
}

export const Avatar = ({ src, name = '', size = 'md', className, ring = false }) => {
  const [failed, setFailed] = useState(false)
  const url = assetUrl(src)

  useEffect(() => setFailed(false), [url])

  const showImage = url && !failed

  return (
    <span
      className={cn(
        'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 font-semibold text-highlight',
        ring && 'ring-2 ring-card',
        sizes[size] || sizes.md,
        className,
      )}
      title={name}
    >
      {showImage ? (
        <img
          src={url}
          alt={name || 'Avatar'}
          className="h-full w-full object-cover"
          onError={() => setFailed(true)}
        />
      ) : (
        initials(name)
      )}
    </span>
  )
}
