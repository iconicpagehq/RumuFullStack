import { useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  BuildingOffice2Icon,
  EllipsisVerticalIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown'
import { EmptyState } from '@/components/ui/EmptyState'
import { formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const SummaryChip = ({ label, value, dotClass }) => (
  <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
    <span className={`h-1.5 w-1.5 rounded-full ${dotClass}`} />
    {label} <span className="font-semibold text-foreground">{formatNumber(value || 0)}</span>
  </span>
)

export const ProjectListPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [deleting, setDeleting] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['inventory', 'projects'],
    queryFn: async () => {
      const response = await realtorApi.inventory.projects()
      return response.data?.data || []
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => realtorApi.inventory.deleteProject(id),
    onSuccess: () => {
      toast.success('Project deleted')
      setDeleting(null)
      queryClient.invalidateQueries({ queryKey: ['inventory', 'projects'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the project.')),
  })

  const projects = data || []
  const filtered = useMemo(() => {
    const query = search.trim().toLowerCase()
    if (!query) return projects
    return projects.filter(
      (project) =>
        project.name?.toLowerCase().includes(query) ||
        project.city?.toLowerCase().includes(query) ||
        project.location?.toLowerCase().includes(query),
    )
  }, [projects, search])

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  return (
    <div>
      <PageHeader
        title="Inventory"
        description="Manage projects, towers, wings, floors and unit availability."
        actions={
          <Link to="/realtor/inventory/new">
            <Button size="sm">
              <PlusIcon className="h-4 w-4" />
              New project
            </Button>
          </Link>
        }
      />

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-muted-foreground">
          {formatNumber(projects.length)} project{projects.length === 1 ? '' : 's'}
        </p>
        <SearchInput
          value={search}
          onChange={setSearch}
          placeholder="Search projects or cities…"
          className="w-full sm:w-80"
        />
      </div>

      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {[1, 2, 3].map((item) => (
            <div key={item} className="skeleton h-56 w-full rounded-xl" />
          ))}
        </div>
      ) : filtered.length === 0 ? (
        <Card>
          <EmptyState
            icon={BuildingOffice2Icon}
            title={search ? 'No matching projects' : 'No inventory projects yet'}
            description={
              search
                ? 'Try a different name or location.'
                : 'Add a project, then build out its towers, floors and units.'
            }
            action={
              !search ? (
                <Link to="/realtor/inventory/new">
                  <Button size="sm">
                    <PlusIcon className="h-4 w-4" />
                    Create project
                  </Button>
                </Link>
              ) : null
            }
          />
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {filtered.map((project) => {
            const summary = project.summary || {}
            return (
              <Card
                key={project.id}
                className="group flex flex-col overflow-hidden transition hover:border-highlight/30 hover:shadow-card"
              >
                <div className="h-1.5 w-full bg-gradient-to-r from-primary-500 to-primary-700" />
                <div className="flex flex-1 flex-col p-5">
                  <div className="flex items-start justify-between gap-3">
                    <Link to={`/realtor/inventory/${project.id}`} className="min-w-0 flex-1">
                      <div className="flex items-center gap-2">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-highlight">
                          <BuildingOffice2Icon className="h-4 w-4" />
                        </span>
                        <div className="min-w-0">
                          <h3 className="truncate text-sm font-semibold text-foreground group-hover:text-highlight">
                            {project.name}
                          </h3>
                          <p className="flex items-center gap-1 truncate text-[11px] text-muted-foreground">
                            <MapPinIcon className="h-3 w-3 shrink-0" />
                            {[project.location, project.city].filter(Boolean).join(', ') || 'Location not set'}
                          </p>
                        </div>
                      </div>
                    </Link>
                    <Dropdown
                      trigger={
                        <Button variant="ghost" size="icon-sm" aria-label="Project actions">
                          <EllipsisVerticalIcon className="h-4 w-4" />
                        </Button>
                      }
                    >
                      <DropdownItem to={`/realtor/inventory/${project.id}`}>Open project</DropdownItem>
                      <DropdownItem
                        to={`/realtor/inventory/${project.id}/edit`}
                        icon={PencilSquareIcon}
                      >
                        Edit project
                      </DropdownItem>
                      <DropdownItem danger icon={TrashIcon} onClick={() => setDeleting(project)}>
                        Delete project
                      </DropdownItem>
                    </Dropdown>
                  </div>

                  <p className="mt-3 line-clamp-2 min-h-[2.5rem] text-sm text-muted-foreground">
                    {project.description || 'No description added yet.'}
                  </p>

                  <div className="mt-4">
                    <div className="flex items-center justify-between text-xs text-muted-foreground">
                      <span>Units</span>
                      <span className="font-semibold text-foreground">
                        {formatNumber(summary.total || 0)} total
                      </span>
                    </div>
                    <div className="mt-2 flex h-2 overflow-hidden rounded-full bg-muted">
                      {summary.total > 0 ? (
                        <>
                          <div
                            className="bg-emerald-500"
                            style={{ width: `${(summary.available / summary.total) * 100}%` }}
                            title={`${summary.available || 0} available`}
                          />
                          <div
                            className="bg-amber-400"
                            style={{ width: `${(summary.hold / summary.total) * 100}%` }}
                            title={`${summary.hold || 0} on hold`}
                          />
                          <div
                            className="bg-blue-500"
                            style={{ width: `${(summary.booked / summary.total) * 100}%` }}
                            title={`${summary.booked || 0} booked`}
                          />
                          <div
                            className="bg-red-500"
                            style={{ width: `${(summary.sold / summary.total) * 100}%` }}
                            title={`${summary.sold || 0} sold`}
                          />
                        </>
                      ) : null}
                    </div>
                    <div className="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                      <SummaryChip label="Available" value={summary.available} dotClass="bg-emerald-500" />
                      <SummaryChip label="Hold" value={summary.hold} dotClass="bg-amber-400" />
                      <SummaryChip label="Booked" value={summary.booked} dotClass="bg-blue-500" />
                      <SummaryChip label="Sold" value={summary.sold} dotClass="bg-red-500" />
                    </div>
                  </div>

                  <div className="mt-4 flex items-center justify-between border-t border-border pt-3">
                    <span className="text-xs text-muted-foreground">
                      {formatNumber(project.towers_count || 0)} tower
                      {project.towers_count === 1 ? '' : 's'}
                    </span>
                    <StatusBadge status={project.status} />
                  </div>
                </div>
              </Card>
            )
          })}
        </div>
      )}

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleteMutation.mutate(deleting.id)}
        loading={deleteMutation.isPending}
        title={`Delete “${deleting?.name}”?`}
        description="All towers, floors and units in this project will be permanently deleted."
        confirmLabel="Delete project"
      />
    </div>
  )
}
