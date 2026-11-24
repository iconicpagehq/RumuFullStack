import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  CheckCircleIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  Squares2X2Icon,
  TrashIcon,
  UserIcon,
  UserPlusIcon,
  XMarkIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { assetUrl } from '@/api/axios'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { DataTable } from '@/components/shared/DataTable'
import { EmptyState } from '@/components/ui/EmptyState'
import { FileUploader } from '@/components/ui/FileUploader'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { UNIT_LAYOUTS, UNIT_STATUSES } from '@/utils/constants'
import { formatCurrency, formatNumber, titleCase } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const emptyUnit = {
  unit_number: '',
  layout: '',
  carpet_area: '',
  built_up_area: '',
  facing: '',
  base_price: '',
  notes: '',
}

const emptyBulk = {
  prefix: '',
  start_number: '',
  end_number: '',
  layout: '',
  carpet_area: '',
  built_up_area: '',
  base_price: '',
}

const numberOrUndefined = (value) =>
  value === '' || value === null || value === undefined || Number.isNaN(Number(value))
    ? undefined
    : Number(value)

const AssignLeadModal = ({ unit, onClose, onAssign, loading }) => {
  const [selectedLeadId, setSelectedLeadId] = useState(unit?.lead?.id || null)
  const [action, setAction] = useState(
    unit?.availability_status === 'available' ? 'hold' : (unit?.availability_status || 'hold')
  )
  const [note, setNote] = useState('')
  const [search, setSearch] = useState('')
  const [filterMode, setFilterMode] = useState('qualified')
  const [formError, setFormError] = useState('')

  const { data: leads = [], isLoading } = useQuery({
    queryKey: ['realtor', 'leads', 'for-inventory-assign'],
    queryFn: async () => {
      const response = await realtorApi.leads.all({ per_page: 100 })
      return response.data?.data || []
    },
    enabled: Boolean(unit),
  })

  const filteredLeads = leads.filter((lead) => {
    if (filterMode === 'qualified' && lead.status !== 'qualified') return false
    if (!search.trim()) return true
    const term = search.toLowerCase()
    return (
      lead.name?.toLowerCase().includes(term) ||
      lead.phone?.toLowerCase().includes(term) ||
      lead.email?.toLowerCase().includes(term)
    )
  })

  const submit = (event) => {
    event.preventDefault()
    if (!selectedLeadId) {
      setFormError('Please select a lead to assign.')
      return
    }
    setFormError('')
    onAssign({
      lead_id: selectedLeadId,
      action,
      note: note.trim() || undefined,
    })
  }

  const handleRelease = () => {
    onAssign({
      lead_id: null,
      action: 'available',
      note: note.trim() || 'Hold released and lead unassigned',
    })
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="md"
      title={`Assign lead · Unit ${unit.unit_number}`}
      description="Link this unit to a qualified lead to place on hold, book, or update status."
    >
      <form onSubmit={submit} className="space-y-4">
        {formError && (
          <Alert variant="danger" onDismiss={() => setFormError('')}>
            {formError}
          </Alert>
        )}

        <div className="rounded-xl border border-border bg-muted/40 p-3 text-xs">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
              <p className="font-semibold text-foreground">Unit {unit.unit_number}</p>
              <p className="text-muted-foreground">
                {unit.layout ? titleCase(unit.layout) : 'Layout not specified'} ·{' '}
                {unit.base_price ? formatCurrency(unit.base_price) : 'Price not set'}
              </p>
            </div>
            <span className="rounded bg-muted px-2 py-0.5 font-medium uppercase tracking-wide text-foreground">
              Current: {unit.availability_status}
            </span>
          </div>
        </div>

        <div>
          <div className="mb-2 flex items-center justify-between">
            <label className="text-xs font-semibold text-foreground">Select lead *</label>
            <div className="flex items-center gap-1 rounded-lg border border-border bg-card p-0.5 text-xs">
              <button
                type="button"
                onClick={() => setFilterMode('qualified')}
                className={`rounded px-2 py-0.5 font-medium transition ${
                  filterMode === 'qualified'
                    ? 'bg-primary/10 text-highlight font-semibold'
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                Qualified ({leads.filter((l) => l.status === 'qualified').length})
              </button>
              <button
                type="button"
                onClick={() => setFilterMode('all')}
                className={`rounded px-2 py-0.5 font-medium transition ${
                  filterMode === 'all'
                    ? 'bg-primary/10 text-highlight font-semibold'
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                All leads ({leads.length})
              </button>
            </div>
          </div>

          <div className="relative mb-2">
            <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by name, phone or email…"
              className="pl-9 text-xs"
            />
          </div>

          <div className="max-h-48 space-y-1.5 overflow-y-auto rounded-xl border border-border bg-card p-1.5">
            {isLoading ? (
              <p className="py-6 text-center text-xs text-muted-foreground">Loading leads…</p>
            ) : filteredLeads.length === 0 ? (
              <div className="py-6 text-center text-xs text-muted-foreground">
                <p>No {filterMode === 'qualified' ? 'qualified ' : ''}leads found.</p>
                {filterMode === 'qualified' && (
                  <button
                    type="button"
                    onClick={() => setFilterMode('all')}
                    className="mt-1 text-xs font-semibold text-highlight hover:underline"
                  >
                    View all leads
                  </button>
                )}
              </div>
            ) : (
              filteredLeads.map((lead) => {
                const isSelected = selectedLeadId === lead.id
                return (
                  <button
                    key={lead.id}
                    type="button"
                    onClick={() => {
                      setSelectedLeadId(lead.id)
                      setFormError('')
                    }}
                    className={`flex w-full items-center justify-between gap-3 rounded-lg border p-2.5 text-left transition ${
                      isSelected
                        ? 'border-highlight bg-primary/5 ring-1 ring-highlight/20'
                        : 'border-border bg-card hover:border-highlight/30 hover:bg-muted/30'
                    }`}
                  >
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-foreground">{lead.name}</p>
                      <p className="truncate text-xs text-muted-foreground">
                        {lead.phone || lead.email || 'No contact info'}
                      </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                      <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold capitalize text-foreground">
                        {lead.status}
                      </span>
                      {isSelected ? (
                        <CheckCircleIcon className="h-5 w-5 text-highlight" />
                      ) : (
                        <div className="h-5 w-5 rounded-full border border-border" />
                      )}
                    </div>
                  </button>
                )
              })
            )}
          </div>
        </div>

        <div>
          <label className="mb-1.5 block text-xs font-semibold text-foreground">
            Action / Unit status *
          </label>
          <div className="grid grid-cols-3 gap-2">
            {[
              { value: 'hold', label: 'Hold', desc: 'Reserve for lead' },
              { value: 'booked', label: 'Booked', desc: 'Advance paid' },
              { value: 'sold', label: 'Sold', desc: 'Deal closed' },
            ].map((opt) => (
              <button
                key={opt.value}
                type="button"
                onClick={() => setAction(opt.value)}
                className={`rounded-lg border p-2 text-center transition ${
                  action === opt.value
                    ? 'border-highlight bg-primary/10 text-highlight ring-1 ring-highlight'
                    : 'border-border bg-card hover:border-highlight/30'
                }`}
              >
                <span className="block text-xs font-semibold">{opt.label}</span>
                <span className="block text-[10px] text-muted-foreground">{opt.desc}</span>
              </button>
            ))}
          </div>
        </div>

        <FormField label="Note / Remarks" htmlFor="assignment_note" hint="Optional internal note.">
          <Input
            id="assignment_note"
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="e.g. Client requested 48h hold"
          />
        </FormField>

        <div className="flex items-center justify-between border-t border-border pt-4">
          {unit.lead_id ? (
            <Button
              type="button"
              variant="danger-outline"
              size="sm"
              disabled={loading}
              onClick={handleRelease}
            >
              Release hold
            </Button>
          ) : (
            <div />
          )}

          <div className="flex gap-2">
            <Button variant="outline" onClick={onClose} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" loading={loading} disabled={!selectedLeadId}>
              {action === 'hold' ? 'Hold for lead' : `Mark as ${titleCase(action)}`}
            </Button>
          </div>
        </div>
      </form>
    </Modal>
  )
}

export const FloorUnitsModal = ({ open, onClose, floor, projectId }) => {
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [status, setStatus] = useState('')
  const [layout, setLayout] = useState('')
  const [mode, setMode] = useState('list')
  const [editing, setEditing] = useState(null)
  const [unitForm, setUnitForm] = useState(emptyUnit)
  const [bulkForm, setBulkForm] = useState(emptyBulk)
  const [imageFile, setImageFile] = useState(null)
  const [formError, setFormError] = useState('')
  const [pendingDelete, setPendingDelete] = useState(null)
  const [assigningUnit, setAssigningUnit] = useState(null)

  useEffect(() => {
    if (!open) return
    setPage(1)
    setStatus('')
    setLayout('')
    setMode('list')
    setEditing(null)
    setUnitForm(emptyUnit)
    setBulkForm(emptyBulk)
    setImageFile(null)
    setFormError('')
    setPendingDelete(null)
    setAssigningUnit(null)
  }, [open, floor?.id])

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['inventory', 'floor-units', floor?.id, { page, status, layout }],
    queryFn: async () => {
      const response = await realtorApi.inventory.floorUnits(floor.id, {
        page,
        status: status || undefined,
        layout: layout || undefined,
      })
      return { units: response.data?.data || [], meta: response.data?.meta }
    },
    enabled: open && Boolean(floor?.id),
    placeholderData: (previous) => previous,
  })

  const invalidateUnits = () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'floor-units', floor?.id] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'project', projectId] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'projects'] })
  }

  const saveUnit = useMutation({
    mutationFn: ({ id, payload }) =>
      id
        ? realtorApi.inventory.updateUnit(id, payload)
        : realtorApi.inventory.addUnit(floor.id, payload),
    onSuccess: () => {
      toast.success(editing ? 'Unit updated' : 'Unit added')
      setMode('list')
      setEditing(null)
      setUnitForm(emptyUnit)
      setImageFile(null)
      setFormError('')
      invalidateUnits()
    },
    onError: (err) => setFormError(getErrorMessage(err, 'Could not save the unit.')),
  })

  const bulkMutation = useMutation({
    mutationFn: (payload) => realtorApi.inventory.bulkUnits(floor.id, payload),
    onSuccess: (response) => {
      const created = response.data?.data?.created
      toast.success(`${created ?? 'Units'} unit${created === 1 ? '' : 's'} created`)
      setMode('list')
      setBulkForm(emptyBulk)
      setFormError('')
      invalidateUnits()
    },
    onError: (err) => setFormError(getErrorMessage(err, 'Could not create units.')),
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, value }) =>
      realtorApi.inventory.updateUnitStatus(id, { availability_status: value }),
    onSuccess: () => {
      toast.success('Unit status updated')
      invalidateUnits()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update the unit status.')),
  })

  const assignMutation = useMutation({
    mutationFn: ({ unitId, payload }) => realtorApi.inventory.assignUnitLead(unitId, payload),
    onSuccess: (_, variables) => {
      toast.success(
        variables.payload.lead_id
          ? `Unit assigned (${variables.payload.action === 'hold' ? 'On Hold' : titleCase(variables.payload.action || 'Hold')})`
          : 'Lead unassigned from unit'
      )
      setAssigningUnit(null)
      invalidateUnits()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not update unit assignment.')),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => realtorApi.inventory.deleteUnit(id),
    onSuccess: () => {
      toast.success('Unit deleted')
      setPendingDelete(null)
      invalidateUnits()
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete the unit.')),
  })

  const beginCreate = () => {
    setEditing(null)
    setUnitForm(emptyUnit)
    setImageFile(null)
    setFormError('')
    setMode('unit')
  }

  const beginEdit = (unit) => {
    setEditing(unit)
    setUnitForm({
      unit_number: unit.unit_number || '',
      layout: unit.layout || '',
      carpet_area: unit.carpet_area ?? '',
      built_up_area: unit.built_up_area ?? '',
      facing: unit.facing || '',
      base_price: unit.base_price ?? '',
      notes: unit.notes || '',
    })
    setImageFile(null)
    setFormError('')
    setMode('unit')
  }

  const submitUnit = (event) => {
    event.preventDefault()
    setFormError('')
    if (!unitForm.unit_number.trim()) {
      setFormError('Unit number is required.')
      return
    }
    const payload = {
      unit_number: unitForm.unit_number.trim(),
      layout: unitForm.layout || undefined,
      carpet_area: numberOrUndefined(unitForm.carpet_area),
      built_up_area: numberOrUndefined(unitForm.built_up_area),
      facing: unitForm.facing.trim() || undefined,
      base_price: numberOrUndefined(unitForm.base_price),
      notes: unitForm.notes.trim() || undefined,
      layout_image: imageFile || undefined,
    }
    saveUnit.mutate({ id: editing?.id, payload })
  }

  const submitBulk = (event) => {
    event.preventDefault()
    setFormError('')
    const start = Number(bulkForm.start_number)
    const end = Number(bulkForm.end_number)
    if (!start || !end || start < 1 || end < start) {
      setFormError('Enter a valid start and end number.')
      return
    }
    if (end - start > 199) {
      setFormError('Create at most 200 units at a time.')
      return
    }
    bulkMutation.mutate({
      prefix: bulkForm.prefix.trim() || undefined,
      start_number: start,
      end_number: end,
      layout: bulkForm.layout || undefined,
      carpet_area: numberOrUndefined(bulkForm.carpet_area),
      built_up_area: numberOrUndefined(bulkForm.built_up_area),
      base_price: numberOrUndefined(bulkForm.base_price),
    })
  }

  const units = data?.units || []
  const meta = data?.meta || { current_page: 1, last_page: 1, per_page: 50, total: 0 }
  const selectedLayout = UNIT_LAYOUTS.find((item) => item.value === layout)
  const closeModal = () => {
    if (mode !== 'list') {
      setMode('list')
      return
    }
    onClose()
  }

  const columns = [
    {
      key: 'unit_number',
      header: 'Unit',
      render: (unit) => (
        <div>
          <p className="text-sm font-semibold text-foreground">{unit.unit_number}</p>
          <p className="text-[11px] text-muted-foreground">{unit.facing || 'Facing not set'}</p>
        </div>
      ),
    },
    {
      key: 'layout',
      header: 'Layout',
      render: (unit) => <span className="text-sm">{unit.layout ? titleCase(unit.layout) : '—'}</span>,
    },
    {
      key: 'area',
      header: 'Area',
      render: (unit) => (
        <div className="text-xs text-muted-foreground">
          <p>{unit.carpet_area ? `${formatNumber(unit.carpet_area)} sq ft carpet` : '—'}</p>
          <p>{unit.built_up_area ? `${formatNumber(unit.built_up_area)} sq ft built-up` : ''}</p>
        </div>
      ),
    },
    {
      key: 'base_price',
      header: 'Base price',
      render: (unit) => (
        <span className="whitespace-nowrap text-sm">{unit.base_price ? formatCurrency(unit.base_price) : '—'}</span>
      ),
    },
    {
      key: 'assigned_lead',
      header: 'Assigned lead',
      render: (unit) => {
        if (unit.lead) {
          return (
            <div className="flex items-center justify-between gap-2 min-w-[170px]">
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-foreground">{unit.lead.name}</p>
                <div className="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                  <span className="inline-flex items-center rounded bg-purple-500/10 px-1.5 py-0.5 text-[10px] font-semibold capitalize text-purple-700 dark:text-purple-300">
                    {unit.lead.status || 'Lead'}
                  </span>
                  {unit.lead.phone && <span className="truncate">· {unit.lead.phone}</span>}
                </div>
              </div>
              <button
                type="button"
                onClick={() => setAssigningUnit(unit)}
                className="shrink-0 rounded-md border border-border px-2 py-1 text-[11px] font-medium text-foreground transition hover:border-highlight/40 hover:bg-muted"
              >
                Change
              </button>
            </div>
          )
        }

        return (
          <button
            type="button"
            onClick={() => setAssigningUnit(unit)}
            className="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-border px-2.5 py-1 text-xs font-medium text-muted-foreground transition hover:border-highlight/50 hover:bg-muted/40 hover:text-highlight"
          >
            <UserPlusIcon className="h-3.5 w-3.5" />
            Assign lead
          </button>
        )
      },
    },
    {
      key: 'availability_status',
      header: 'Status',
      render: (unit) => (
        <select
          value={unit.availability_status}
          onChange={(event) =>
            statusMutation.mutate({ id: unit.id, value: event.target.value })
          }
          disabled={statusMutation.isPending}
          className="h-8 rounded-lg border border-border bg-card px-2 text-xs font-medium text-foreground outline-none focus:border-highlight"
        >
          {UNIT_STATUSES.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'w-20 text-right',
      render: (unit) => (
        <div className="flex items-center justify-end gap-1">
          <button
            type="button"
            onClick={() => beginEdit(unit)}
            className="rounded-lg p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            aria-label={`Edit unit ${unit.unit_number}`}
          >
            <PencilSquareIcon className="h-4 w-4" />
          </button>
          <button
            type="button"
            onClick={() => setPendingDelete(unit)}
            className="rounded-lg p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
            aria-label={`Delete unit ${unit.unit_number}`}
          >
            <TrashIcon className="h-4 w-4" />
          </button>
        </div>
      ),
    },
  ]

  const itemMeta = floor?.structureMeta || {}
  const unitNounSingular = itemMeta.unitLabel || 'unit'
  const unitNounPlural = itemMeta.unitLabelPlural || 'units'
  const floorNoun = itemMeta.floorLabel || 'Floor'

  return (
    <Modal
      open={open}
      onClose={closeModal}
      size="xl"
      className="max-w-6xl"
      title={
        mode === 'unit'
          ? `${editing ? 'Edit' : 'Add'} ${unitNounSingular}`
          : mode === 'bulk'
            ? `Bulk add ${unitNounPlural}`
            : `${titleCase(unitNounPlural)} · ${floor?.label || floorNoun}`
      }
      description={
        mode === 'list'
          ? `${floor?.wing_name ? `${floor.wing_name} · ` : ''}${formatNumber(meta.total)} ${unitNounPlural}`
          : (floor?.label || floorNoun)
      }
      footer={
        mode === 'list' ? (
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
        ) : null
      }
    >
      <div className="max-h-[70vh] overflow-y-auto pr-1 scrollbar-thin">
        {formError && (
          <Alert variant="danger" className="mb-4" onDismiss={() => setFormError('')}>
            {formError}
          </Alert>
        )}

        {mode === 'list' && (
          <>
            {pendingDelete && (
              <Alert variant="danger" className="mb-4" title="Delete this unit?">
                Unit {pendingDelete.unit_number} will be permanently removed.
                <div className="mt-2.5 flex gap-2">
                  <Button
                    size="sm"
                    variant="danger"
                    onClick={() => deleteMutation.mutate(pendingDelete.id)}
                    loading={deleteMutation.isPending}
                  >
                    Delete
                  </Button>
                  <Button size="sm" variant="outline" onClick={() => setPendingDelete(null)}>
                    Cancel
                  </Button>
                </div>
              </Alert>
            )}

            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div className="flex flex-wrap items-center gap-2">
                <select
                  value={status}
                  onChange={(event) => {
                    setStatus(event.target.value)
                    setPage(1)
                  }}
                  className="h-9 rounded-lg border border-border bg-card px-2.5 text-xs font-medium text-foreground outline-none focus:border-highlight"
                >
                  <option value="">All statuses</option>
                  {UNIT_STATUSES.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                <select
                  value={layout}
                  onChange={(event) => {
                    setLayout(event.target.value)
                    setPage(1)
                  }}
                  className="h-9 rounded-lg border border-border bg-card px-2.5 text-xs font-medium text-foreground outline-none focus:border-highlight"
                >
                  <option value="">All layouts</option>
                  {UNIT_LAYOUTS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                {selectedLayout && (
                  <span className="text-[11px] text-muted-foreground">Filtered by {selectedLayout.label}</span>
                )}
              </div>
              <div className="flex flex-wrap gap-2">
                <Button size="sm" variant="outline" onClick={() => setMode('bulk')}>
                  <Squares2X2Icon className="h-4 w-4" />
                  Bulk add
                </Button>
                <Button size="sm" onClick={beginCreate}>
                  <PlusIcon className="h-4 w-4" />
                  Add {unitNounSingular}
                </Button>
              </div>
            </div>

            {isError ? (
              <Alert variant="danger" title={`Could not load ${unitNounPlural}`}>
                {getErrorMessage(error)}
                <div className="mt-2">
                  <Button size="sm" variant="outline" onClick={refetch}>
                    Retry
                  </Button>
                </div>
              </Alert>
            ) : (
              <>
                <DataTable
                  columns={columns}
                  rows={units}
                  loading={isLoading}
                  rowKey="id"
                  emptyState={
                    <EmptyState
                      icon={Squares2X2Icon}
                      title={`No ${unitNounPlural} here yet`}
                      description={`Add ${unitNounPlural} individually or create a numbered range in bulk.`}
                      className="py-8"
                      action={
                        <div className="flex gap-2">
                          <Button size="sm" variant="outline" onClick={() => setMode('bulk')}>
                            Bulk add
                          </Button>
                          <Button size="sm" onClick={beginCreate}>
                            Add {unitNounSingular}
                          </Button>
                        </div>
                      }
                    />
                  }
                />
                <Pagination
                  page={meta.current_page || 1}
                  lastPage={meta.last_page || 1}
                  total={meta.total || 0}
                  perPage={meta.per_page || 50}
                  onPageChange={setPage}
                />
              </>
            )}
          </>
        )}

        {mode === 'unit' && (
          <form onSubmit={submitUnit} className="space-y-4">
            <button
              type="button"
              onClick={() => setMode('list')}
              className="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground"
            >
              <ArrowLeftIcon className="h-3.5 w-3.5" />
              Back to units
            </button>

            <div className="grid gap-4 sm:grid-cols-2">
              <FormField label="Unit number" htmlFor="unit_number" required>
                <Input
                  id="unit_number"
                  value={unitForm.unit_number}
                  onChange={(event) => setUnitForm({ ...unitForm, unit_number: event.target.value })}
                  placeholder="e.g. 301"
                  autoFocus
                />
              </FormField>
              <FormField label="Layout" htmlFor="unit_layout">
                <Select
                  id="unit_layout"
                  value={unitForm.layout}
                  onChange={(event) => setUnitForm({ ...unitForm, layout: event.target.value })}
                >
                  <option value="">Not specified</option>
                  {UNIT_LAYOUTS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </Select>
              </FormField>
              <FormField label="Carpet area (sq ft)" htmlFor="carpet_area">
                <Input
                  id="carpet_area"
                  type="number"
                  min="0"
                  step="0.01"
                  value={unitForm.carpet_area}
                  onChange={(event) => setUnitForm({ ...unitForm, carpet_area: event.target.value })}
                  placeholder="e.g. 850"
                />
              </FormField>
              <FormField label="Built-up area (sq ft)" htmlFor="built_up_area">
                <Input
                  id="built_up_area"
                  type="number"
                  min="0"
                  step="0.01"
                  value={unitForm.built_up_area}
                  onChange={(event) => setUnitForm({ ...unitForm, built_up_area: event.target.value })}
                  placeholder="e.g. 1120"
                />
              </FormField>
              <FormField label="Facing" htmlFor="facing">
                <Input
                  id="facing"
                  value={unitForm.facing}
                  onChange={(event) => setUnitForm({ ...unitForm, facing: event.target.value })}
                  placeholder="e.g. East"
                />
              </FormField>
              <FormField label="Base price (₹)" htmlFor="base_price">
                <Input
                  id="base_price"
                  type="number"
                  min="0"
                  step="0.01"
                  value={unitForm.base_price}
                  onChange={(event) => setUnitForm({ ...unitForm, base_price: event.target.value })}
                  placeholder="e.g. 7500000"
                />
              </FormField>
            </div>

            <FormField label="Notes" htmlFor="unit_notes">
              <Textarea
                id="unit_notes"
                rows={2}
                value={unitForm.notes}
                onChange={(event) => setUnitForm({ ...unitForm, notes: event.target.value })}
                placeholder="Internal notes about this unit"
              />
            </FormField>

            <div>
              <p className="form-label">Layout image</p>
              {editing?.layout_image && !imageFile && (
                <a
                  href={assetUrl(editing.layout_image)}
                  target="_blank"
                  rel="noreferrer"
                  className="mb-2 inline-block text-xs font-medium text-highlight hover:underline"
                >
                  View current layout image
                </a>
              )}
              <FileUploader
                file={imageFile}
                onFileChange={setImageFile}
                accept="image/jpeg,image/png,image/webp"
                hint="JPG, PNG or WebP up to 4 MB"
              />
            </div>

            <div className="flex justify-end gap-2 border-t border-border pt-4">
              <Button variant="outline" onClick={() => setMode('list')} disabled={saveUnit.isPending}>
                Cancel
              </Button>
              <Button type="submit" loading={saveUnit.isPending}>
                {editing ? 'Save unit' : 'Add unit'}
              </Button>
            </div>
          </form>
        )}

        {mode === 'bulk' && (
          <form onSubmit={submitBulk} className="space-y-4">
            <button
              type="button"
              onClick={() => setMode('list')}
              className="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition hover:text-foreground"
            >
              <ArrowLeftIcon className="h-3.5 w-3.5" />
              Back to units
            </button>

            <Alert variant="info">
              Creates a sequential range of units on this floor in one go. Existing unit numbers are
              skipped or reported by the server.
            </Alert>

            <div className="grid gap-4 sm:grid-cols-3">
              <FormField label="Prefix" htmlFor="bulk_prefix">
                <Input
                  id="bulk_prefix"
                  value={bulkForm.prefix}
                  onChange={(event) => setBulkForm({ ...bulkForm, prefix: event.target.value })}
                  placeholder="e.g. A-"
                />
              </FormField>
              <FormField label="Start number" htmlFor="start_number" required>
                <Input
                  id="start_number"
                  type="number"
                  min="1"
                  value={bulkForm.start_number}
                  onChange={(event) => setBulkForm({ ...bulkForm, start_number: event.target.value })}
                  placeholder="301"
                />
              </FormField>
              <FormField label="End number" htmlFor="end_number" required>
                <Input
                  id="end_number"
                  type="number"
                  min="1"
                  value={bulkForm.end_number}
                  onChange={(event) => setBulkForm({ ...bulkForm, end_number: event.target.value })}
                  placeholder="310"
                />
              </FormField>
              <FormField label="Layout" htmlFor="bulk_layout">
                <Select
                  id="bulk_layout"
                  value={bulkForm.layout}
                  onChange={(event) => setBulkForm({ ...bulkForm, layout: event.target.value })}
                >
                  <option value="">Not specified</option>
                  {UNIT_LAYOUTS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </Select>
              </FormField>
              <FormField label="Carpet area (sq ft)" htmlFor="bulk_carpet">
                <Input
                  id="bulk_carpet"
                  type="number"
                  min="0"
                  value={bulkForm.carpet_area}
                  onChange={(event) => setBulkForm({ ...bulkForm, carpet_area: event.target.value })}
                />
              </FormField>
              <FormField label="Built-up area (sq ft)" htmlFor="bulk_built">
                <Input
                  id="bulk_built"
                  type="number"
                  min="0"
                  value={bulkForm.built_up_area}
                  onChange={(event) => setBulkForm({ ...bulkForm, built_up_area: event.target.value })}
                />
              </FormField>
              <FormField label="Base price (₹)" htmlFor="bulk_price">
                <Input
                  id="bulk_price"
                  type="number"
                  min="0"
                  value={bulkForm.base_price}
                  onChange={(event) => setBulkForm({ ...bulkForm, base_price: event.target.value })}
                />
              </FormField>
            </div>

            <div className="flex justify-end gap-2 border-t border-border pt-4">
              <Button variant="outline" onClick={() => setMode('list')} disabled={bulkMutation.isPending}>
                Cancel
              </Button>
              <Button type="submit" loading={bulkMutation.isPending}>
                Create units
              </Button>
            </div>
          </form>
        )}
      </div>

      {assigningUnit && (
        <AssignLeadModal
          unit={assigningUnit}
          onClose={() => setAssigningUnit(null)}
          onAssign={(payload) => assignMutation.mutate({ unitId: assigningUnit.id, payload })}
          loading={assignMutation.isPending}
        />
      )}
    </Modal>
  )
}
