import { Link } from 'react-router-dom'
import { cn } from '@/utils/cn'
import logoWhite from '@/assets/rumu-logo.png'
import logoDark from '@/assets/rumu-logo-ink.png'

const logoSizes = {
  sm: 'h-7',
  md: 'h-9',
  lg: 'h-11',
}

export const Brand = ({ to = '/', size = 'md', className, logoClassName, dark = false }) => {
  const sizeClass = logoSizes[size] || logoSizes.md
  const logoClass = cn('w-auto select-none', sizeClass, logoClassName)

  return (
    <Link to={to} className={cn('inline-flex items-center', className)} aria-label="Rumu">
      {dark ? (
        <img
          src={logoWhite}
          alt="Rumu"
          className={logoClass}
          draggable={false}
          onError={(e) => {
            if (e.currentTarget.src !== logoDark) {
              e.currentTarget.src = logoDark
            }
          }}
        />
      ) : (
        <>
          <img
            src={logoDark}
            alt="Rumu"
            className={cn(logoClass, 'dark:hidden')}
            draggable={false}
          />
          <img
            src={logoWhite}
            alt=""
            aria-hidden="true"
            className={cn(logoClass, 'hidden dark:block')}
            draggable={false}
          />
        </>
      )}
    </Link>
  )
}
