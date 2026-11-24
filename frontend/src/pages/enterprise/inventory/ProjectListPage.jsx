import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  BuildingOffice2Icon,
  BuildingStorefrontIcon,
  HomeModernIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusIcon,
  Squares2X2Icon,
  TrashIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { PageHeader } from '@/components/shared/PageHeader'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatsCard } from '@/components/shared/StatsCard'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ProjectFormModal } from '@/components/enterprise/inventory/ProjectFormModal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const availabilitySegments = [
  { key: 'available', label: 'Available', className: 'bg-emerald-500' },
  { key: 'hold', label: 'On hold', className: 'bg-violet-500' },
  { key: 'booked', label: 'Booked', className: 'bg-primary' },
  { key: 'sold', label: 'Sold', className: 'bg-rose-500' },
]

export const ProjectListPage = () => {
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, canUpdateUnitStatus } = useEnterprise()
  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'inventory-projects'],
    queryFn: async () => {
      const response = await enterpriseApi.inventory.projects()
      return response.data?.data || []
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (project) => enterpriseApi.inventory.deleteProject(project.id),
    onSuccess: () => {
      toast.success('Project deleted')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'notifications'] })
      setDeleteTarget(null)
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this project.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const projects = data || []
  const totals = projects.reduce(
    (acc, project) => {
      const summary = project.summary || {}
      acc.towers += project.towers_count || 0
      acc.units += summary.total || 0
      acc.available += summary.available || 0
      acc.booked += (summary.booked || 0) + (summary.sold || 0)
      return acc
    },
    { towers: 0, units: 0, available: 0, booked: 0 },
  )

  return (
    <div>
      <PageHeader
        title="Inventory"
        description="Projects, towers and units available to your sales teams."
        actions={
          isAdmin ? (
            <Button
              onClick={() => {
                setEditing(null)
                setFormOpen(true)
              }}
            >
              <PlusIcon className="h-4 w-4" />
              Add project
            </Button>
          ) : null
        }
      />

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Projects" value={formatNumber(projects.length)} icon={BuildingOffice2Icon} tone="purple" loading={isLoading} />
        <StatsCard label="Towers" value={formatNumber(totals.towers)} icon={BuildingStorefrontIcon} tone="info" loading={isLoading} />
        <StatsCard label="Available units" value={formatNumber(totals.available)} icon={HomeModernIcon} tone="success" loading={isLoading} />
        <StatsCard
          label="Booked or sold"
          value={formatNumber(totals.booked)}
          icon={Squares2X2Icon}
          tone="primary"
          hint={`${formatNumber(totals.units)} units in total`}
          loading={isLoading}
        />
      </div>

      {isLoading ? (
        <div className="mt-5 grid gap-5 lg:grid-cols-2">
          {Array.from({ length: 4 }, (_, index) => (
            <div key={index} className="skeleton h-64 rounded-2xl" />
          ))}
        </div>
      ) : projects.length === 0 ? (
        <Card className="mt-5">
          <CardContent>
            <EmptyState
              icon={BuildingOffice2Icon}
              title="No projects yet"
              description={
                isAdmin
                  ? 'Create your first project, then add towers, floors and units to make inventory available for bookings.'
                  : 'Your company administrator has not added inventory projects yet.'
              }
              action={
                isAdmin ? (
                  <Button
                    onClick={() => {
                      setEditing(null)
                      setFormOpen(true)
                    }}
                  >
                    <PlusIcon className="h-4 w-4" />
                    Add project
                  </Button>
                ) : null
              }
            />
          </CardContent>
        </Card>
      ) : (
        <div className="mt-5 grid gap-5 lg:grid-cols-2">
          {projects.map((project) => {
            const summary = project.summary || {}
            const total = summary.total || 0
            const location = [project.location, project.city].filter(Boolean).join(', ')

            return (
              <Card key={project.id} className="flex flex-col">
                <CardContent className="flex flex-1 flex-col p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <Link
                          to={`/enterprise/inventory/${project.id}`}
                          className="truncate text-base font-semibold text-foreground transition hover:text-highlight"
                        >
                          {project.name}
                        </Link>
                        <StatusBadge status={project.status} />
                      </div>
                      <p className="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                        <MapPinIcon className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate">{location || 'Location not set'}</span>
                        <span aria-hidden="true">·</span>
                        <span>
                          {project.towers_count || 0} tower{project.towers_count === 1 ? '' : 's'}
                        </span>
                      </p>
                    </div>

                    {isAdmin && (
                      <div className="flex shrink-0 items-center gap-1">
                        <button
                          type="button"
                          onClick={() => {
                            setEditing(project)
                            setFormOpen(true)
                          }}
                          className="rounded-lg p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                          aria-label={`Edit ${project.name}`}
                        >
                          <PencilSquareIcon className="h-4 w-4" />
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeleteTarget(project)}
                          className="rounded-lg p-2 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
                          aria-label={`Delete ${project.name}`}
                        >
                          <TrashIcon className="h-4 w-4" />
                        </button>
                      </div>
                    )}
                  </div>

                  <p className="mt-3 line-clamp-2 min-h-[40px] text-sm text-muted-foreground">
                    {project.description || 'No description provided.'}
                  </p>

                  <div className="mt-4">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-medium text-foreground">{formatNumber(total)} units</span>
                      <span className="text-muted-foreground">
                        {total > 0 ? `${Math.round(((summary.available || 0) / total) * 100)}% available` : 'No units'}
                      </span>
                    </div>
                    <div className="mt-2 flex h-2 overflow-hidden rounded-full bg-muted">
                      {total > 0 &&
                        availabilitySegments.map((segment) => {
                          const value = summary[segment.key] || 0
                          if (!value) return null
                          return (
                            <div
                              key={segment.key}
                              className={segment.className}
                              style={{ width: `${(value / total) * 100}%` }}
                              title={`${segment.label}: ${value}`}
                            />
                          )
                        })}
                    </div>
                    <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                      {availabilitySegments.map((segment) => (
                        <span key={segment.key} className="inline-flex items-center gap-1.5 text-[11px] text-muted-foreground">
                          <span className={`h-2 w-2 rounded-full ${segment.className}`} aria-hidden="true" />
                          {summary[segment.key] || 0} {segment.label.toLowerCase()}
                        </span>
                      ))}
                    </div>
                  </div>

                  <div className="mt-5 flex items-center justify-between border-t border-border pt-4">
                    <p className="text-xs text-muted-foreground">
                      {canUpdateUnitStatus ? 'Tap a unit to update its status.' : 'View towers, floors and units.'}
                    </p>
                    <Button variant="outline" size="sm" onClick={() => navigate(`/enterprise/inventory/${project.id}`)}>
                      Open inventory
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )
          })}
        </div>
      )}

      <ProjectFormModal open={formOpen} onClose={() => setFormOpen(false)} project={editing} />

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        onConfirm={() => deleteMutation.mutate(deleteTarget)}
        loading={deleteMutation.isPending}
        title={`Delete ${deleteTarget?.name || 'project'}?`}
        description="This permanently deletes the project, its towers, floors and units. Projects with booked or sold units cannot be deleted."
        confirmLabel="Delete project"
      />
    </div>
  )
}
