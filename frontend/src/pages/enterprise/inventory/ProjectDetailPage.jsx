import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
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
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { StatsCard } from '@/components/shared/StatsCard'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent, CardHeader } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useEnterprise } from '@/hooks/useEnterprise'
import { ProjectFormModal } from '@/components/enterprise/inventory/ProjectFormModal'
import { HierarchyFormModal } from '@/components/enterprise/inventory/HierarchyFormModal'
import { UnitFormModal } from '@/components/enterprise/inventory/UnitFormModal'
import { BulkUnitsModal } from '@/components/enterprise/inventory/BulkUnitsModal'
import { UnitStatusModal } from '@/components/enterprise/inventory/UnitStatusModal'
import { formatCurrency, formatNumber } from '@/utils/formatters'
import { inventoryStatusTone, layoutLabel, nextUnitStatuses, statusLabel } from '@/utils/inventory'
import { getErrorMessage } from '@/utils/errors'

const UnitStatusBadge = ({ status }) => (
  <Badge variant={inventoryStatusTone(status)} dot>
    {statusLabel(status)}
  </Badge>
)

const IconButton = ({ label, onClick, children, danger = false }) => (
  <button
    type="button"
    onClick={onClick}
    className={`rounded-lg p-2 transition ${
      danger
        ? 'text-muted-foreground hover:bg-destructive/10 hover:text-destructive'
        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
    }`}
    aria-label={label}
    title={label}
  >
    {children}
  </button>
)

export const ProjectDetailPage = () => {
  const { id } = useParams()
  const projectId = Number(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAdmin, canUpdateUnitStatus } = useEnterprise()

  const [projectFormOpen, setProjectFormOpen] = useState(false)
  const [hierarchy, setHierarchy] = useState(null)
  const [unitForm, setUnitForm] = useState(null)
  const [bulkFloor, setBulkFloor] = useState(null)
  const [statusUnit, setStatusUnit] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)

  const { data: project, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['enterprise', 'inventory-project', projectId],
    queryFn: async () => {
      const response = await enterpriseApi.inventory.project(projectId)
      return response.data?.data
    },
    enabled: Number.isFinite(projectId),
  })

  const deleteMutation = useMutation({
    mutationFn: () => {
      const { type, item } = deleteTarget
      if (type === 'project') return enterpriseApi.inventory.deleteProject(item.id)
      if (type === 'tower') return enterpriseApi.inventory.deleteTower(item.id)
      if (type === 'wing') return enterpriseApi.inventory.deleteWing(item.id)
      if (type === 'floor') return enterpriseApi.inventory.deleteFloor(item.id)
      return enterpriseApi.inventory.deleteUnit(item.id)
    },
    onSuccess: () => {
      toast.success('Deleted')
      if (deleteTarget?.type === 'project') {
        queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
        queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
        setDeleteTarget(null)
        navigate('/enterprise/inventory')
        return
      }
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', projectId] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      setDeleteTarget(null)
    },
    onError: (deleteError) => {
      toast.error(getErrorMessage(deleteError, 'Could not delete this item.'))
    },
  })

  if (isError) return <ErrorState error={error} onRetry={refetch} />
  if (isLoading || !project) {
    return (
      <div className="space-y-5">
        <div className="skeleton h-20 rounded-2xl" />
        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
          {Array.from({ length: 4 }, (_, index) => (
            <div key={index} className="skeleton h-28 rounded-2xl" />
          ))}
        </div>
        <div className="skeleton h-96 rounded-2xl" />
      </div>
    )
  }

  const summary = project.summary || {}
  const towers = project.towers || []
  const location = [project.location, project.city].filter(Boolean).join(', ')

  const openHierarchy = (config) => setHierarchy(config)

  const unitColumns = [
    {
      key: 'unit_number',
      header: 'Unit',
      render: (unit) => (
        <div className="min-w-0">
          <p className="whitespace-nowrap text-sm font-semibold text-foreground">{unit.unit_number}</p>
          <p className="text-[11px] text-muted-foreground">{layoutLabel(unit.layout)}</p>
        </div>
      ),
    },
    {
      key: 'area',
      header: 'Area',
      render: (unit) => (
        <div className="whitespace-nowrap text-xs">
          <p className="text-foreground">
            {unit.carpet_area ? `${formatNumber(unit.carpet_area)} sq ft carpet` : 'Carpet not set'}
          </p>
          <p className="text-muted-foreground">
            {unit.built_up_area ? `${formatNumber(unit.built_up_area)} sq ft built-up` : 'Built-up not set'}
          </p>
        </div>
      ),
    },
    {
      key: 'facing',
      header: 'Facing',
      render: (unit) => <span className="text-xs text-muted-foreground">{unit.facing || '—'}</span>,
    },
    {
      key: 'base_price',
      header: 'Base price',
      render: (unit) => (
        <span className="whitespace-nowrap text-sm font-medium text-foreground">
          {unit.base_price ? formatCurrency(unit.base_price) : '—'}
        </span>
      ),
    },
    {
      key: 'availability_status',
      header: 'Status',
      render: (unit) => <UnitStatusBadge status={unit.availability_status} />,
    },
    {
      key: 'actions',
      header: '',
      headerClassName: 'text-right',
      className: 'text-right',
      stopPropagation: true,
      render: (unit) => (
        <div className="flex items-center justify-end gap-1">
          {canUpdateUnitStatus && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => setStatusUnit(unit)}
              disabled={nextUnitStatuses(unit.availability_status).length === 0}
            >
              {nextUnitStatuses(unit.availability_status).length === 0 ? 'Sold' : 'Status'}
            </Button>
          )}
          {isAdmin && (
            <>
              <IconButton
                label={`Edit unit ${unit.unit_number}`}
                onClick={() =>
                  setUnitForm({
                    floor: {
                      id: unit.floor_id,
                      label: project.name,
                      projectId,
                    },
                    unit,
                  })
                }
              >
                <PencilSquareIcon className="h-4 w-4" />
              </IconButton>
              <IconButton
                label={`Delete unit ${unit.unit_number}`}
                danger
                onClick={() =>
                  setDeleteTarget({
                    type: 'unit',
                    item: unit,
                    label: `unit ${unit.unit_number}`,
                  })
                }
              >
                <TrashIcon className="h-4 w-4" />
              </IconButton>
            </>
          )}
        </div>
      ),
    },
  ]

  return (
    <div>
      <PageHeader
        title={project.name}
        description={project.description || 'Towers, floors and units for this project.'}
        breadcrumbs={[
          { label: 'Inventory', to: '/enterprise/inventory' },
          { label: project.name },
        ]}
        actions={
          isAdmin ? (
            <>
              <Button variant="outline" onClick={() => setProjectFormOpen(true)}>
                <PencilSquareIcon className="h-4 w-4" />
                Edit project
              </Button>
              <Button onClick={() => openHierarchy({ kind: 'tower', mode: 'create', projectId })}>
                <PlusIcon className="h-4 w-4" />
                Add tower
              </Button>
            </>
          ) : canUpdateUnitStatus ? (
            <p className="text-xs text-muted-foreground">You can update unit availability on this page.</p>
          ) : null
        }
      />

      <div className="mb-5 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-border bg-card px-4 py-3 text-xs text-muted-foreground">
        <span className="inline-flex items-center gap-1.5">
          <MapPinIcon className="h-3.5 w-3.5" />
          {location || 'Location not set'}
        </span>
        <StatusBadge status={project.status} />
        <span>{towers.length} towers</span>
        <span>{formatNumber(summary.total || 0)} units</span>
      </div>

      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Available" value={formatNumber(summary.available || 0)} icon={HomeModernIcon} tone="success" />
        <StatsCard label="On hold" value={formatNumber(summary.hold || 0)} icon={BuildingStorefrontIcon} tone="purple" />
        <StatsCard label="Booked" value={formatNumber(summary.booked || 0)} icon={Squares2X2Icon} tone="primary" />
        <StatsCard label="Sold" value={formatNumber(summary.sold || 0)} icon={BuildingOffice2Icon} tone="danger" />
      </div>

      <div className="mt-5 space-y-5">
        {towers.length === 0 ? (
          <Card>
            <CardContent>
              <EmptyState
                icon={BuildingStorefrontIcon}
                title="No towers yet"
                description={
                  isAdmin
                    ? 'Add a tower, then create wings, floors and units.'
                    : 'No towers have been added to this project yet.'
                }
                action={
                  isAdmin ? (
                    <Button onClick={() => openHierarchy({ kind: 'tower', mode: 'create', projectId })}>
                      <PlusIcon className="h-4 w-4" />
                      Add tower
                    </Button>
                  ) : null
                }
              />
            </CardContent>
          </Card>
        ) : (
          towers.map((tower) => {
            const wings = tower.wings || []
            const floors = tower.floors || []
            const towerUnitCount = floors.reduce((sum, floor) => sum + (floor.units?.length || 0), 0)

            return (
              <Card key={tower.id}>
                <CardHeader className="flex flex-wrap items-center justify-between gap-3 border-b border-border">
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="text-sm font-semibold text-foreground">{tower.name}</h2>
                      <Badge variant="neutral">{floors.length} floors</Badge>
                      <Badge variant="neutral">{formatNumber(towerUnitCount)} units</Badge>
                    </div>
                    {wings.length > 0 && (
                      <div className="mt-2 flex flex-wrap items-center gap-2">
                        {wings.map((wing) => (
                          <span
                            key={wing.id}
                            className="inline-flex items-center gap-1 rounded-full border border-border bg-muted/50 py-1 pl-2.5 pr-1 text-[11px] font-medium text-foreground"
                          >
                            {wing.name}
                            {isAdmin && (
                              <>
                                <button
                                  type="button"
                                  onClick={() =>
                                    openHierarchy({
                                      kind: 'wing',
                                      mode: 'edit',
                                      projectId,
                                      tower,
                                      wing,
                                    })
                                  }
                                  className="rounded-full p-0.5 text-muted-foreground transition hover:text-foreground"
                                  aria-label={`Edit wing ${wing.name}`}
                                >
                                  <PencilSquareIcon className="h-3 w-3" />
                                </button>
                                <button
                                  type="button"
                                  onClick={() =>
                                    setDeleteTarget({
                                      type: 'wing',
                                      item: wing,
                                      label: `wing ${wing.name}`,
                                    })
                                  }
                                  className="rounded-full p-0.5 text-muted-foreground transition hover:text-destructive"
                                  aria-label={`Delete wing ${wing.name}`}
                                >
                                  <TrashIcon className="h-3 w-3" />
                                </button>
                              </>
                            )}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>

                  {isAdmin && (
                    <div className="flex flex-wrap items-center gap-1.5">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openHierarchy({ kind: 'wing', mode: 'create', projectId, tower })}
                      >
                        Add wing
                      </Button>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                          openHierarchy({ kind: 'floor', mode: 'create', projectId, tower, wings })
                        }
                      >
                        Add floor
                      </Button>
                      <IconButton
                        label={`Edit tower ${tower.name}`}
                        onClick={() => openHierarchy({ kind: 'tower', mode: 'edit', projectId, tower })}
                      >
                        <PencilSquareIcon className="h-4 w-4" />
                      </IconButton>
                      <IconButton
                        label={`Delete tower ${tower.name}`}
                        danger
                        onClick={() =>
                          setDeleteTarget({ type: 'tower', item: tower, label: `tower ${tower.name}` })
                        }
                      >
                        <TrashIcon className="h-4 w-4" />
                      </IconButton>
                    </div>
                  )}
                </CardHeader>

                <CardContent className="space-y-4 p-4">
                  {floors.length === 0 ? (
                    <EmptyState
                      icon={BuildingOffice2Icon}
                      title="No floors in this tower"
                      description={
                        isAdmin ? 'Add a floor to start adding units.' : 'No floors have been added yet.'
                      }
                      action={
                        isAdmin ? (
                          <Button
                            size="sm"
                            onClick={() =>
                              openHierarchy({ kind: 'floor', mode: 'create', projectId, tower, wings })
                            }
                          >
                            <PlusIcon className="h-4 w-4" />
                            Add floor
                          </Button>
                        ) : null
                      }
                    />
                  ) : (
                    floors.map((floor) => {
                      const units = floor.units || []
                      return (
                        <div key={floor.id} className="overflow-hidden rounded-xl border border-border">
                          <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-muted/30 px-4 py-2.5">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className="text-xs font-semibold text-foreground">{floor.label}</span>
                              <span className="text-[11px] text-muted-foreground">
                                {units.length} unit{units.length === 1 ? '' : 's'}
                              </span>
                            </div>
                            {isAdmin && (
                              <div className="flex flex-wrap items-center gap-1.5">
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => setUnitForm({ floor, unit: null })}
                                >
                                  Add unit
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setBulkFloor(floor)}>
                                  Bulk add
                                </Button>
                                <IconButton
                                  label={`Edit ${floor.label}`}
                                  onClick={() =>
                                    openHierarchy({ kind: 'floor', mode: 'edit', projectId, tower, floor })
                                  }
                                >
                                  <PencilSquareIcon className="h-4 w-4" />
                                </IconButton>
                                <IconButton
                                  label={`Delete ${floor.label}`}
                                  danger
                                  onClick={() =>
                                    setDeleteTarget({ type: 'floor', item: floor, label: floor.label })
                                  }
                                >
                                  <TrashIcon className="h-4 w-4" />
                                </IconButton>
                              </div>
                            )}
                          </div>

                          <DataTable
                            className="rounded-none border-0"
                            columns={unitColumns}
                            rows={units}
                            rowKey="id"
                            emptyState={
                              <EmptyState
                                icon={HomeModernIcon}
                                title="No units on this floor"
                                description={
                                  isAdmin
                                    ? 'Add units individually or generate a numbered batch.'
                                    : 'Units have not been added to this floor yet.'
                                }
                                action={
                                  isAdmin ? (
                                    <div className="flex flex-wrap items-center justify-center gap-2">
                                      <Button size="sm" onClick={() => setUnitForm({ floor, unit: null })}>
                                        Add unit
                                      </Button>
                                      <Button variant="outline" size="sm" onClick={() => setBulkFloor(floor)}>
                                        Bulk add
                                      </Button>
                                    </div>
                                  ) : null
                                }
                              />
                            }
                          />
                        </div>
                      )
                    })
                  )}
                </CardContent>
              </Card>
            )
          })
        )}

        {isAdmin && towers.length > 0 && (
          <Card>
            <CardContent className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-sm font-semibold text-foreground">Add another tower</p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                  Towers group wings, floors and units inside this project.
                </p>
              </div>
              <Button variant="outline" onClick={() => openHierarchy({ kind: 'tower', mode: 'create', projectId })}>
                <PlusIcon className="h-4 w-4" />
                Add tower
              </Button>
            </CardContent>
          </Card>
        )}
      </div>

      <ProjectFormModal
        open={projectFormOpen}
        onClose={() => setProjectFormOpen(false)}
        project={project}
      />

      <HierarchyFormModal open={Boolean(hierarchy)} onClose={() => setHierarchy(null)} config={hierarchy} />

      <UnitFormModal
        open={Boolean(unitForm)}
        onClose={() => setUnitForm(null)}
        floor={unitForm?.floor}
        unit={unitForm?.unit}
        projectId={projectId}
      />

      <BulkUnitsModal
        open={Boolean(bulkFloor)}
        onClose={() => setBulkFloor(null)}
        floor={bulkFloor}
        projectId={projectId}
      />

      <UnitStatusModal
        open={Boolean(statusUnit)}
        onClose={() => setStatusUnit(null)}
        unit={statusUnit}
        projectId={projectId}
      />

      <ConfirmDialog
        open={Boolean(deleteTarget)}
        onClose={() => setDeleteTarget(null)}
        onConfirm={() => deleteMutation.mutate()}
        loading={deleteMutation.isPending}
        title={`Delete ${deleteTarget?.label || 'item'}?`}
        description="This cannot be undone. Booked or sold units block deletion of their project, tower, wing or floor."
        confirmLabel="Delete"
      />
    </div>
  )
}
