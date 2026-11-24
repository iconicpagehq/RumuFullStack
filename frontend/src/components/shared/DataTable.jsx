import { EmptyState } from '@/components/ui/EmptyState'
import { cn } from '@/utils/cn'

const SkeletonRow = ({ columns }) => (
  <tr>
    {columns.map((column, index) => (
      <td key={column.key || index} className="table-td">
        <div className={cn('skeleton h-4', index === 0 ? 'w-32' : 'w-20')} />
      </td>
    ))}
  </tr>
)

export const DataTable = ({
  columns = [],
  rows = [],
  loading = false,
  emptyState,
  rowKey = 'id',
  onRowClick,
  className,
  footer,
  skeletonRows = 5,
}) => {
  const clickable = Boolean(onRowClick)

  return (
    <div className={cn('table-shell', className)}>
      <div className="overflow-x-auto scrollbar-thin">
        <table className="min-w-full divide-y divide-border">
          <thead className="bg-muted/50">
            <tr>
              {columns.map((column, index) => (
                <th
                  key={column.key || index}
                  scope="col"
                  className={cn('table-th', column.headerClassName)}
                >
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {loading ? (
              Array.from({ length: skeletonRows }, (_, index) => (
                <SkeletonRow key={index} columns={columns} />
              ))
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length}>
                  {emptyState || (
                    <EmptyState
                      title="No results found"
                      description="Try adjusting your search or filters."
                    />
                  )}
                </td>
              </tr>
            ) : (
              rows.map((row, rowIndex) => (
                <tr
                  key={row?.[rowKey] ?? rowIndex}
                  onClick={clickable ? () => onRowClick(row) : undefined}
                  className={cn(
                    'transition-colors',
                    clickable && 'cursor-pointer hover:bg-muted/40',
                  )}
                >
                  {columns.map((column, columnIndex) => (
                    <td
                      key={column.key || columnIndex}
                      className={cn('table-td', column.className)}
                      onClick={
                        column.stopPropagation
                          ? (event) => event.stopPropagation()
                          : undefined
                      }
                    >
                      {column.render ? column.render(row) : row?.[column.key] ?? '—'}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
      {footer && <div className="border-t border-border px-4 py-2">{footer}</div>}
    </div>
  )
}
