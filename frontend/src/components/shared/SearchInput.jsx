import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/react/20/solid'
import { cn } from '@/utils/cn'

export const SearchInput = ({
  value,
  onChange,
  placeholder = 'Search…',
  className,
  inputClassName,
  onClear,
}) => (
  <div className={cn('relative', className)}>
    <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
    <input
      type="search"
      value={value}
      onChange={(event) => onChange(event.target.value)}
      placeholder={placeholder}
      className={cn('input-base pl-9 pr-9 [&::-webkit-search-cancel-button]:hidden', inputClassName)}
    />
    {value && (
      <button
        type="button"
        onClick={() => {
          onChange('')
          onClear?.()
        }}
        className="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground"
        aria-label="Clear search"
      >
        <XMarkIcon className="h-4 w-4" />
      </button>
    )}
  </div>
)
