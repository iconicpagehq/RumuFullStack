import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ArrowLeftIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  BuildingStorefrontIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  ChevronRightIcon,
  ClockIcon,
  HomeModernIcon,
  MapPinIcon,
  PencilSquareIcon,
  PhotoIcon,
  PlusIcon,
  Squares2X2Icon,
  TrashIcon,
} from '@heroicons/react/20/solid'
import { realtorApi } from '@/api/realtor'
import { assetUrl } from '@/api/axios'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatsCard } from '@/components/shared/StatsCard'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { ErrorState } from '@/components/shared/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { FileUploader } from '@/components/ui/FileUploader'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { PageLoader } from '@/components/ui/Spinner'
import { formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'
import { FloorUnitsModal } from './FloorUnitsModal'

const STRUCTURE_TYPES = [
  {
    id: 'tower',
    label: 'Tower',
    fullName: 'Tower / Building',
    subtitle: 'Multi-storey building with wings, floors and flats.',
    nameLabel: 'Tower name',
    placeholder: 'e.g. Tower A or Building 1',
    icon: BuildingOffice2Icon,
    defaultPrefix: 'Tower ',
  },
  {
    id: 'row_house',
    label: 'Row House',
    fullName: 'Row House / Villa',
    subtitle: 'Independent row house, villa, townhouse or bungalow.',
    nameLabel: 'Row house name / number',
    placeholder: 'e.g. Row House 1 or Villa Orchid',
    icon: HomeModernIcon,
    defaultPrefix: 'Row House ',
  },
  {
    id: 'plot',
    label: 'Plot',
    fullName: 'Plot / Land',
    subtitle: 'Plotted development, layout phase or land parcel.',
    nameLabel: 'Plot / Layout name',
    placeholder: 'e.g. Plot 101 or Phase 1',
    icon: Squares2X2Icon,
    defaultPrefix: 'Plot ',
  },
  {
    id: 'custom',
    label: 'Structure',
    fullName: 'Commercial / Other',
    subtitle: 'Commercial block, shops, clubhouse or custom structure.',
    nameLabel: 'Structure name',
    placeholder: 'e.g. Commercial Block C, Clubhouse',
    icon: BuildingStorefrontIcon,
    defaultPrefix: '',
  },
]

const getStructureMeta = (name = '') => {
  const lower = (name || '').toLowerCase()
  if (
    lower.includes('row') ||
    lower.includes('villa') ||
    lower.includes('house') ||
    lower.includes('bungalow') ||
    lower.includes('townhouse') ||
    lower.includes('duplex') ||
    lower.includes('cottage')
  ) {
    return {
      type: 'row_house',
      label: 'Row House',
      icon: HomeModernIcon,
      hasWings: false,
      wingLabel: null,
      addWingText: null,
      floorLabel: 'Level',
      floorLabelPlural: 'levels',
      addFloorText: 'Add level',
      unitLabel: 'unit',
      unitLabelPlural: 'units',
      unitsActionLabel: 'Manage units',
      emptyFloorsTitle: 'No levels yet',
      emptyFloorsDesc: 'Add levels (e.g. Ground Floor, 1st Floor) to this row house, then manage rooms or units.',
      formatFloorName: (num) => {
        if (num === 0) return 'Ground Level (G)'
        if (num === 1) return 'First Floor (Level 1)'
        if (num === 2) return 'Second Floor (Level 2)'
        if (num === 3) return 'Third Floor (Level 3)'
        if (num === -1) return 'Basement'
        return `Level ${num}`
      },
      floorFieldLabel: 'Level number',
      floorFieldPlaceholder: '0 for Ground, 1 for 1st Floor',
      floorFieldHint: '0 = Ground Level, 1 = 1st Floor, 2 = 2nd Floor, 3 = Terrace',
      quickLevels: [
        { label: '+ Ground Level (0)', number: 0 },
        { label: '+ First Floor (1)', number: 1 },
      ],
    }
  }

  if (
    lower.includes('plot') ||
    lower.includes('land') ||
    lower.includes('layout') ||
    lower.includes('parcel') ||
    lower.includes('sector') ||
    lower.includes('phase')
  ) {
    return {
      type: 'plot',
      label: 'Plot / Land',
      icon: Squares2X2Icon,
      hasWings: false,
      wingLabel: null,
      addWingText: null,
      floorLabel: 'Phase / Sector',
      floorLabelPlural: 'phases',
      addFloorText: 'Add phase / sector',
      unitLabel: 'plot',
      unitLabelPlural: 'plots',
      unitsActionLabel: 'Manage plots',
      emptyFloorsTitle: 'No phases or sectors yet',
      emptyFloorsDesc: 'Add a phase or sector to start organizing plots for this layout.',
      formatFloorName: (num) => `Phase ${num}`,
      floorFieldLabel: 'Phase / Sector number',
      floorFieldPlaceholder: 'e.g. 1 for Phase 1, 2 for Sector 2',
      floorFieldHint: 'Use a phase or sector number (e.g. 1, 2, 3)',
      quickLevels: [
        { label: '+ Phase 1', number: 1 },
        { label: '+ Phase 2', number: 2 },
      ],
    }
  }

  if (
    lower.includes('shop') ||
    lower.includes('commercial') ||
    lower.includes('retail') ||
    lower.includes('mall') ||
    lower.includes('office') ||
    lower.includes('plaza') ||
    lower.includes('bazaar') ||
    lower.includes('club')
  ) {
    return {
      type: 'commercial',
      label: 'Commercial',
      icon: BuildingStorefrontIcon,
      hasWings: true,
      wingLabel: 'Block / Wing',
      addWingText: 'Add block',
      floorLabel: 'Floor',
      floorLabelPlural: 'floors',
      addFloorText: 'Add floor',
      unitLabel: 'shop/office',
      unitLabelPlural: 'units',
      unitsActionLabel: 'Manage units',
      emptyFloorsTitle: 'No floors yet',
      emptyFloorsDesc: 'Add floors to this commercial block, then create shops, offices or spaces.',
      formatFloorName: (num) => {
        if (num === 0) return 'Ground Floor'
        if (num < 0) return `Basement ${Math.abs(num)}`
        return `Floor ${num}`
      },
      floorFieldLabel: 'Floor number',
      floorFieldPlaceholder: 'e.g. 0 for Ground, 1 for 1st Floor',
      floorFieldHint: 'Use 0 for Ground Floor, 1 for 1st Floor, etc.',
      quickLevels: null,
    }
  }

  // Default: Tower / Building
  return {
    type: 'tower',
    label: 'Tower',
    icon: BuildingOffice2Icon,
    hasWings: true,
    wingLabel: 'Wing',
    addWingText: 'Add wing',
    floorLabel: 'Floor',
    floorLabelPlural: 'floors',
    addFloorText: 'Add floor',
    unitLabel: 'flat',
    unitLabelPlural: 'units',
    unitsActionLabel: 'Manage units',
    emptyFloorsTitle: 'No floors yet',
    emptyFloorsDesc: 'Add floors to this tower, then create units on each floor.',
    formatFloorName: (num) => {
      if (num === 0) return 'Ground Floor'
      if (num < 0) return `Basement ${Math.abs(num)}`
      return `Floor ${num}`
    },
    floorFieldLabel: 'Floor number',
    floorFieldPlaceholder: 'e.g. 1',
    floorFieldHint: 'Floor number (e.g. 1, 2, 3...)',
    quickLevels: null,
  }
}

const getStructureIcon = (name = '') => getStructureMeta(name).icon

const EntityModal = ({ state, error, loading, onClose, onSubmit }) => {
  const isTower = state.type === 'tower'
  const isEdit = state.mode === 'edit'
  const inferredMeta = isTower ? getStructureMeta(state.item?.name) : null
  const initialType = inferredMeta
    ? (STRUCTURE_TYPES.find((t) => t.id === inferredMeta.type) || STRUCTURE_TYPES[0])
    : STRUCTURE_TYPES[0]
  const [step, setStep] = useState(isTower && !isEdit ? 'select_type' : 'details')
  const [selectedType, setSelectedType] = useState(initialType)
  const [name, setName] = useState(state.item?.name || '')
  const [sortOrder, setSortOrder] = useState(state.item?.sort_order ?? '')
  const [formError, setFormError] = useState('')

  const handleSelectType = (type) => {
    setSelectedType(type)
    setName(type.defaultPrefix || '')
    setStep('details')
    setFormError('')
  }

  const submit = (event) => {
    event.preventDefault()
    if (!name.trim()) {
      const entityLabel = isTower ? (selectedType?.nameLabel || 'Structure name') : 'Wing name'
      setFormError(`${entityLabel} is required.`)
      return
    }
    setFormError('')
    onSubmit({
      name: name.trim(),
      sort_order: sortOrder === '' ? undefined : Number(sortOrder),
    })
  }

  if (step === 'select_type') {
    return (
      <Modal
        open
        onClose={onClose}
        size="md"
        title="What would you like to add?"
        description="Choose the type of structure for this project."
        footer={
          <Button variant="outline" onClick={onClose}>
            Cancel
          </Button>
        }
      >
        <div className="space-y-3 pb-1">
          {STRUCTURE_TYPES.map((type) => {
            const Icon = type.icon
            return (
              <button
                key={type.id}
                type="button"
                onClick={() => handleSelectType(type)}
                className="group flex w-full items-center gap-3.5 rounded-xl border border-border bg-card p-4 text-left transition-all hover:border-highlight/60 hover:bg-muted/40 hover:shadow-sm"
              >
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-highlight transition group-hover:scale-105 group-hover:bg-primary/20">
                  <Icon className="h-6 w-6" />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block text-sm font-semibold text-foreground group-hover:text-highlight">
                    {type.fullName}
                  </span>
                  <span className="mt-0.5 block text-xs text-muted-foreground leading-relaxed">
                    {type.subtitle}
                  </span>
                </span>
                <ChevronRightIcon className="h-5 w-5 shrink-0 text-muted-foreground/50 transition-transform group-hover:translate-x-1 group-hover:text-highlight" />
              </button>
            )
          })}
        </div>
      </Modal>
    )
  }

  const modalTitle = isEdit
    ? `Edit ${isTower ? 'structure' : 'wing'}`
    : `Add ${isTower ? selectedType.label : 'wing'}`

  const modalDescription = isTower
    ? (isEdit ? 'Update the details for this structure.' : selectedType.subtitle)
    : `Group floors inside ${state.tower?.name || 'this tower'}.`

  const inputLabel = isTower ? selectedType.nameLabel : 'Wing name'
  const inputPlaceholder = isTower ? selectedType.placeholder : 'e.g. East Wing'

  return (
    <Modal
      open
      onClose={onClose}
      size="sm"
      title={modalTitle}
      description={modalDescription}
    >
      <form onSubmit={submit} className="space-y-4">
        {(formError || error) && (
          <Alert variant="danger" onDismiss={() => setFormError('')}>
            {formError || error}
          </Alert>
        )}
        <FormField label={inputLabel} htmlFor="structure_name" required>
          <Input
            id="structure_name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            placeholder={inputPlaceholder}
            autoFocus
          />
        </FormField>

        <details className="group pt-0.5">
          <summary className="cursor-pointer text-xs font-medium text-muted-foreground transition hover:text-foreground inline-flex items-center gap-1.5 select-none">
            <span>Advanced: Custom display order</span>
            <span className="text-[10px] text-muted-foreground/70">(optional)</span>
          </summary>
          <div className="mt-2 rounded-lg bg-muted/30 p-3 border border-border/60">
            <FormField label="Sort order" htmlFor="structure_sort" hint="Lower numbers appear first (e.g. 1, 2, 3). Defaults to 0 for automatic ordering.">
              <Input
                id="structure_sort"
                type="number"
                min="0"
                value={sortOrder}
                onChange={(event) => setSortOrder(event.target.value)}
                placeholder="0"
              />
            </FormField>
          </div>
        </details>

        <div className="flex items-center justify-between border-t border-border pt-4 mt-6">
          {isTower && !isEdit ? (
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => {
                setFormError('')
                setStep('select_type')
              }}
              disabled={loading}
            >
              <ArrowLeftIcon className="h-4 w-4 mr-1" />
              Back
            </Button>
          ) : (
            <div />
          )}
          <div className="flex gap-2">
            <Button variant="outline" onClick={onClose} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" loading={loading}>
              {isEdit ? 'Save changes' : `Add ${isTower ? selectedType.label : 'wing'}`}
            </Button>
          </div>
        </div>
      </form>
    </Modal>
  )
}

const FloorModal = ({ state, error, loading, onClose, onSubmit }) => {
  const isEdit = state.mode === 'edit'
  const meta = getStructureMeta(state.tower?.name)
  const [floorNumber, setFloorNumber] = useState(
    state.floor?.floor_number ?? state.prefillNumber ?? '',
  )
  const [wingId, setWingId] = useState(
    state.wing?.id ?? state.floor?.inventory_wing_id ?? '',
  )
  const [file, setFile] = useState(null)
  const [formError, setFormError] = useState('')
  const wingName = state.tower?.wings?.find(
    (wing) => String(wing.id) === String(state.floor?.inventory_wing_id),
  )?.name

  const submit = (event) => {
    event.preventDefault()
    if (floorNumber === '' || !Number.isInteger(Number(floorNumber))) {
      setFormError(`Enter a valid ${meta.floorLabel.toLowerCase()} number.`)
      return
    }
    setFormError('')
    onSubmit({
      floor_number: Number(floorNumber),
      inventory_wing_id: !meta.hasWings || wingId === '' ? null : Number(wingId),
      layout_image: file || undefined,
    })
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="sm"
      title={`${isEdit ? 'Edit' : 'Add'} ${meta.floorLabel.toLowerCase()}`}
      description={
        isEdit
          ? (state.floor?.label || meta.formatFloorName(state.floor?.floor_number))
          : `Create a ${meta.floorLabel.toLowerCase()} in ${state.tower?.name || 'this structure'}${state.wing ? ` · ${state.wing.name}` : ''}.`
      }
    >
      <form onSubmit={submit} className="space-y-4">
        {(formError || error) && (
          <Alert variant="danger" onDismiss={() => setFormError('')}>
            {formError || error}
          </Alert>
        )}
        <FormField
          label={meta.floorFieldLabel}
          htmlFor="floor_number"
          hint={meta.floorFieldHint}
          required
        >
          <Input
            id="floor_number"
            type="number"
            value={floorNumber}
            onChange={(event) => setFloorNumber(event.target.value)}
            placeholder={meta.floorFieldPlaceholder}
            autoFocus
          />
        </FormField>

        {meta.hasWings && (
          isEdit ? (
            <FormField label={meta.wingLabel || 'Wing'}>
              <Input value={wingName || 'Not assigned'} disabled readOnly />
            </FormField>
          ) : (
            <FormField
              label={meta.wingLabel || 'Wing'}
              htmlFor="floor_wing"
              hint={`Leave blank to add directly under the ${meta.label.toLowerCase()}.`}
            >
              <Select
                id="floor_wing"
                value={wingId}
                onChange={(event) => setWingId(event.target.value)}
                disabled={Boolean(state.wing)}
              >
                <option value="">No {meta.wingLabel?.toLowerCase() || 'wing'}</option>
                {(state.tower?.wings || []).map((wing) => (
                  <option key={wing.id} value={wing.id}>
                    {wing.name}
                  </option>
                ))}
              </Select>
            </FormField>
          )
        )}

        <div>
          <p className="form-label">Layout image</p>
          {isEdit && state.floor?.layout_image && !file && (
            <a
              href={assetUrl(state.floor.layout_image)}
              target="_blank"
              rel="noreferrer"
              className="mb-2 inline-block text-xs font-medium text-highlight hover:underline"
            >
              View current layout image
            </a>
          )}
          <FileUploader
            file={file}
            onFileChange={setFile}
            accept="image/jpeg,image/png,image/webp"
            hint="JPG, PNG or WebP up to 4 MB"
          />
        </div>

        <div className="flex justify-end gap-2 border-t border-border pt-4">
          <Button variant="outline" onClick={onClose} disabled={loading}>
            Cancel
          </Button>
          <Button type="submit" loading={loading}>
            {isEdit ? `Save ${meta.floorLabel.toLowerCase()}` : `Add ${meta.floorLabel.toLowerCase()}`}
          </Button>
        </div>
      </form>
    </Modal>
  )
}

const FloorCard = ({ floor, meta, onManage, onEdit, onDelete }) => {
  const units = floor.units || []
  const total = floor.units_count ?? units.length
  const available = units.filter((unit) => unit.availability_status === 'available').length
  const displayName = meta?.formatFloorName ? meta.formatFloorName(floor.floor_number) : `Floor ${floor.floor_number}`

  return (
    <div className="rounded-xl border border-border bg-card p-3.5">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold text-foreground">{displayName}</p>
          <p className="mt-0.5 flex items-center gap-1.5 text-[11px] text-muted-foreground">
            {formatNumber(total)} {meta?.unitLabelPlural || 'units'}
            {total > 0 && (
              <>
                <span className="h-1 w-1 rounded-full bg-border" />
                <span className="text-emerald-600 dark:text-emerald-400">
                  {formatNumber(available)} available
                </span>
              </>
            )}
          </p>
        </div>
        <div className="flex shrink-0 items-center gap-0.5">
          {floor.layout_image && (
            <a
              href={assetUrl(floor.layout_image)}
              target="_blank"
              rel="noreferrer"
              className="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
              aria-label={`View layout for ${displayName}`}
            >
              <PhotoIcon className="h-3.5 w-3.5" />
            </a>
          )}
          <button
            type="button"
            onClick={() => onEdit(floor)}
            className="rounded-md p-1.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
            aria-label={`Edit ${displayName}`}
          >
            <PencilSquareIcon className="h-3.5 w-3.5" />
          </button>
          <button
            type="button"
            onClick={() => onDelete(floor)}
            className="rounded-md p-1.5 text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive"
            aria-label={`Delete ${displayName}`}
          >
            <TrashIcon className="h-3.5 w-3.5" />
          </button>
        </div>
      </div>
      <Button size="sm" variant="outline" className="mt-3 w-full" onClick={() => onManage(floor)}>
        {meta?.unitsActionLabel || 'Manage units'}
      </Button>
    </div>
  )
}

export const ProjectDetailPage = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [entityModal, setEntityModal] = useState(null)
  const [floorModal, setFloorModal] = useState(null)
  const [unitsFloor, setUnitsFloor] = useState(null)
  const [deleting, setDeleting] = useState(null)
  const [entityError, setEntityError] = useState('')
  const [floorError, setFloorError] = useState('')

  const { data: project, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['inventory', 'project', id],
    queryFn: async () => {
      const response = await realtorApi.inventory.project(id)
      return response.data?.data
    },
    enabled: Boolean(id),
    retry: false,
  })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'project', id] })
    queryClient.invalidateQueries({ queryKey: ['inventory', 'projects'] })
  }

  const saveEntity = useMutation({
    mutationFn: ({ type, itemId, towerId, values }) => {
      if (type === 'tower') {
        return itemId
          ? realtorApi.inventory.updateTower(itemId, values)
          : realtorApi.inventory.addTower(id, values)
      }
      return itemId
        ? realtorApi.inventory.updateWing(itemId, values)
        : realtorApi.inventory.addWing(towerId, values)
    },
    onSuccess: (_, variables) => {
      const label = variables.type === 'tower' ? 'Structure' : 'Wing'
      toast.success(variables.itemId ? `${label} updated` : `${label} added`)
      setEntityModal(null)
      setEntityError('')
      refresh()
    },
    onError: (err) => setEntityError(getErrorMessage(err, 'Could not save your changes.')),
  })

  const saveFloor = useMutation({
    mutationFn: ({ floor, towerId, wingId, payload }) => {
      if (floor) return realtorApi.inventory.updateFloor(floor.id, payload)
      if (wingId) return realtorApi.inventory.addFloorToWing(wingId, payload)
      return realtorApi.inventory.addFloor(towerId, payload)
    },
    onSuccess: (_, variables) => {
      const meta = getStructureMeta(floorModal?.tower?.name)
      const label = meta.floorLabel || 'Floor'
      toast.success(variables.floor ? `${label} updated` : `${label} added`)
      setFloorModal(null)
      setFloorError('')
      refresh()
    },
    onError: (err) => setFloorError(getErrorMessage(err, 'Could not save the floor.')),
  })

  const deleteMutation = useMutation({
    mutationFn: ({ type, itemId }) => {
      if (type === 'tower') return realtorApi.inventory.deleteTower(itemId)
      if (type === 'wing') return realtorApi.inventory.deleteWing(itemId)
      if (type === 'floor') return realtorApi.inventory.deleteFloor(itemId)
      return realtorApi.inventory.deleteProject(itemId)
    },
    onSuccess: (_, variables) => {
      toast.success(
        variables.type === 'project'
          ? 'Project deleted'
          : `${variables.type.charAt(0).toUpperCase()}${variables.type.slice(1)} deleted`,
      )
      setDeleting(null)
      if (variables.type === 'project') {
        queryClient.invalidateQueries({ queryKey: ['inventory', 'projects'] })
        navigate('/realtor/inventory')
      } else {
        refresh()
      }
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete this item.')),
  })

  if (isLoading) return <PageLoader />
  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const summary = project.summary || { available: 0, hold: 0, booked: 0, sold: 0, total: 0 }
  const towers = project.towers || []
  const totalFloors = towers.reduce((sum, tower) => sum + (tower.floors?.length || 0), 0)

  const openTowerCreate = () => {
    setEntityError('')
    setEntityModal({ type: 'tower', mode: 'create' })
  }
  const openTowerEdit = (tower) => {
    setEntityError('')
    setEntityModal({ type: 'tower', mode: 'edit', item: tower })
  }
  const openWingCreate = (tower) => {
    setEntityError('')
    setEntityModal({ type: 'wing', mode: 'create', tower })
  }
  const openWingEdit = (tower, wing) => {
    setEntityError('')
    setEntityModal({ type: 'wing', mode: 'edit', tower, item: wing })
  }
  const openFloorCreate = (tower, wing = null, prefillNumber = null) => {
    setFloorError('')
    setFloorModal({ mode: 'create', tower, wing, prefillNumber })
  }
  const openFloorEdit = (tower, floor) => {
    setFloorError('')
    setFloorModal({ mode: 'edit', tower, floor })
  }

  const confirmDelete = () => {
    if (!deleting) return
    deleteMutation.mutate({ type: deleting.type, itemId: deleting.id })
  }

  const deleteCopy = {
    project: {
      title: `Delete “${project.name}”?`,
      description: 'All structures, floors and units in this project will be permanently deleted.',
      label: 'Delete project',
    },
    tower: {
      title: `Delete “${deleting?.name}”?`,
      description: 'All wings, levels and units inside this structure will be permanently deleted.',
      label: 'Delete structure',
    },
    wing: {
      title: `Delete wing “${deleting?.name}”?`,
      description: 'Floors and units assigned to this wing may also be removed.',
      label: 'Delete wing',
    },
    floor: {
      title: `Delete “${deleting?.name}”?`,
      description: 'All units inside will be permanently deleted.',
      label: 'Delete',
    },
  }
  const deleteDialog = deleting ? deleteCopy[deleting.type] : deleteCopy.floor

  return (
    <div>
      <PageHeader
        breadcrumbs={[{ label: 'Inventory', to: '/realtor/inventory' }, { label: project.name }]}
        title={
          <span className="inline-flex flex-wrap items-center gap-2.5">
            {project.name}
            <StatusBadge status={project.status} />
          </span>
        }
        description={
          [project.location, project.city].filter(Boolean).join(', ') ||
          'Location not set for this project.'
        }
        actions={
          <>
            <Button variant="danger-outline" size="sm" onClick={() => setDeleting({ type: 'project', id: project.id, name: project.name })}>
              <TrashIcon className="h-4 w-4" />
              Delete
            </Button>
            <Link to={`/realtor/inventory/${project.id}/edit`}>
              <Button variant="outline" size="sm">
                <PencilSquareIcon className="h-4 w-4" />
                Edit project
              </Button>
            </Link>
            <Button size="sm" onClick={openTowerCreate}>
              <PlusIcon className="h-4 w-4" />
              Add structure
            </Button>
          </>
        }
      />

      <div className="mb-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatsCard
          label="Available"
          value={formatNumber(summary.available || 0)}
          icon={CheckBadgeIcon}
          tone="success"
          hint={`of ${formatNumber(summary.total || 0)} units`}
        />
        <StatsCard
          label="On hold"
          value={formatNumber(summary.hold || 0)}
          icon={ClockIcon}
          tone="warning"
          hint="Reserved temporarily"
        />
        <StatsCard
          label="Booked"
          value={formatNumber(summary.booked || 0)}
          icon={CalendarDaysIcon}
          tone="info"
          hint="Awaiting registration"
        />
        <StatsCard
          label="Sold"
          value={formatNumber(summary.sold || 0)}
          icon={BanknotesIcon}
          tone="danger"
          hint="Closed deals"
        />
      </div>

      <Card className="mb-6 p-5">
        <div className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground">
          <span className="inline-flex items-center gap-1.5">
            <MapPinIcon className="h-4 w-4" />
            {[project.location, project.city].filter(Boolean).join(', ') || 'Location not set'}
          </span>
          {project.created_at && (
            <span className="inline-flex items-center gap-1.5">
              <CalendarDaysIcon className="h-4 w-4" />
              Added {formatDate(project.created_at)}
            </span>
          )}
          <span>
            {formatNumber(towers.length)} structure{towers.length === 1 ? '' : 's'} ·{' '}
            {formatNumber(totalFloors)} level{totalFloors === 1 ? '' : 's'}
          </span>
        </div>

        {project.description && (
          <p className="mt-3 max-w-3xl text-sm text-muted-foreground">{project.description}</p>
        )}

        <div className="mt-4 flex h-2 overflow-hidden rounded-full bg-muted">
          {summary.total > 0 && (
            <>
              <div
                className="bg-emerald-500"
                style={{ width: `${((summary.available || 0) / summary.total) * 100}%` }}
                title={`${summary.available || 0} available`}
              />
              <div
                className="bg-amber-400"
                style={{ width: `${((summary.hold || 0) / summary.total) * 100}%` }}
                title={`${summary.hold || 0} on hold`}
              />
              <div
                className="bg-blue-500"
                style={{ width: `${((summary.booked || 0) / summary.total) * 100}%` }}
                title={`${summary.booked || 0} booked`}
              />
              <div
                className="bg-red-500"
                style={{ width: `${((summary.sold || 0) / summary.total) * 100}%` }}
                title={`${summary.sold || 0} sold`}
              />
            </>
          )}
        </div>
      </Card>

      <div className="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 className="text-base font-semibold text-foreground">Towers & structures</h2>
          <p className="text-xs text-muted-foreground">
            Add towers, row houses, or plots, then organize levels/floors and manage units.
          </p>
        </div>
      </div>

      {towers.length === 0 ? (
        <Card>
          <EmptyState
            icon={BuildingOffice2Icon}
            title="No structures yet"
            description="Add your first tower, row house, or plot to start mapping units for this project."
            action={
              <Button size="sm" onClick={openTowerCreate}>
                <PlusIcon className="h-4 w-4" />
                Add structure
              </Button>
            }
          />
        </Card>
      ) : (
        <div className="space-y-4">
          {towers.map((tower) => {
            const meta = getStructureMeta(tower.name)
            const StructureIcon = meta.icon
            const floors = [...(tower.floors || [])].sort(
              (a, b) => a.floor_number - b.floor_number,
            )
            const wings = [...(tower.wings || [])].sort(
              (a, b) => (a.sort_order || 0) - (b.sort_order || 0),
            )
            const towerUnits = floors.reduce(
              (sum, floor) => sum + (floor.units_count ?? floor.units?.length ?? 0),
              0,
            )
            const groups = !meta.hasWings
              ? [
                  {
                    key: 'all',
                    name: '',
                    floors,
                  },
                ]
              : [
                  {
                    key: 'unassigned',
                    name: wings.length > 0 ? 'Unassigned floors' : '',
                    floors: floors.filter((f) => !f.inventory_wing_id),
                  },
                  ...wings.map((wing) => ({
                    key: `wing-${wing.id}`,
                    name: wing.name,
                    floors: floors.filter((floor) => floor.inventory_wing_id === wing.id),
                  })),
                ].filter((group) => group.floors.length > 0)

            return (
              <Card key={tower.id} className="overflow-hidden">
                <div className="border-b border-border p-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-3">
                      <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-highlight">
                        <StructureIcon className="h-5 w-5" />
                      </span>
                      <div className="min-w-0">
                        <h3 className="truncate text-sm font-semibold text-foreground">{tower.name}</h3>
                        <p className="text-xs text-muted-foreground">
                          {meta.hasWings && (
                            <>
                              {formatNumber(wings.length)} {meta.wingLabel?.toLowerCase() || 'wing'}
                              {wings.length === 1 ? '' : 's'} ·{' '}
                            </>
                          )}
                          {formatNumber(floors.length)} {meta.floorLabelPlural} ·{' '}
                          {formatNumber(towerUnits)} {meta.unitLabelPlural}
                        </p>
                      </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                      {meta.hasWings && (
                        <Button size="sm" variant="outline" onClick={() => openWingCreate(tower)}>
                          <PlusIcon className="h-4 w-4" />
                          {meta.addWingText}
                        </Button>
                      )}
                      <Button size="sm" variant="outline" onClick={() => openFloorCreate(tower)}>
                        <PlusIcon className="h-4 w-4" />
                        {meta.addFloorText}
                      </Button>
                      <Button
                        size="icon-sm"
                        variant="ghost"
                        onClick={() => openTowerEdit(tower)}
                        aria-label={`Edit ${tower.name}`}
                      >
                        <PencilSquareIcon className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon-sm"
                        variant="ghost"
                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                        onClick={() => setDeleting({ type: 'tower', id: tower.id, name: tower.name })}
                        aria-label={`Delete ${tower.name}`}
                      >
                        <TrashIcon className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>

                  {meta.hasWings && (
                    <div className="mt-4">
                      <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {meta.wingLabel ? `${meta.wingLabel}s` : 'Wings'}
                      </p>
                      {wings.length === 0 ? (
                        <p className="mt-1.5 text-xs text-muted-foreground">
                          No {meta.wingLabel ? meta.wingLabel.toLowerCase() : 'wing'}s yet — add{' '}
                          {meta.wingLabel ? meta.wingLabel.toLowerCase() : 'wing'}s to group floors, or add
                          floors directly to the {meta.label.toLowerCase()}.
                        </p>
                      ) : (
                        <div className="mt-2 flex flex-wrap gap-2">
                          {wings.map((wing) => (
                            <span
                              key={wing.id}
                              className="inline-flex items-center gap-1 rounded-lg border border-border bg-muted/40 py-1 pl-2.5 pr-1"
                            >
                              <span className="text-xs font-medium text-foreground">{wing.name}</span>
                              <button
                                type="button"
                                onClick={() => openWingEdit(tower, wing)}
                                className="rounded p-1 text-muted-foreground transition hover:bg-card hover:text-foreground"
                                aria-label={`Edit ${meta.wingLabel?.toLowerCase() || 'wing'} ${wing.name}`}
                              >
                                <PencilSquareIcon className="h-3.5 w-3.5" />
                              </button>
                              <button
                                type="button"
                                onClick={() =>
                                  setDeleting({
                                    type: 'wing',
                                    id: wing.id,
                                    name: wing.name,
                                  })
                                }
                                className="rounded p-1 text-muted-foreground transition hover:bg-card hover:text-destructive"
                                aria-label={`Delete ${meta.wingLabel?.toLowerCase() || 'wing'} ${wing.name}`}
                              >
                                <TrashIcon className="h-3.5 w-3.5" />
                              </button>
                            </span>
                          ))}
                        </div>
                      )}
                    </div>
                  )}
                </div>

                <div className="p-5">
                  {floors.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border py-8 text-center">
                      <p className="text-sm font-medium text-foreground">{meta.emptyFloorsTitle}</p>
                      <p className="max-w-sm text-xs text-muted-foreground">{meta.emptyFloorsDesc}</p>
                      <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
                        <Button size="sm" variant="outline" onClick={() => openFloorCreate(tower)}>
                          <PlusIcon className="h-4 w-4" />
                          {meta.addFloorText}
                        </Button>
                        {meta.quickLevels?.map((quick) => (
                          <Button
                            key={quick.number}
                            size="sm"
                            variant="secondary"
                            onClick={() => openFloorCreate(tower, null, quick.number)}
                          >
                            {quick.label}
                          </Button>
                        ))}
                      </div>
                    </div>
                  ) : (
                    <div className="space-y-4">
                      {groups.map((group) => (
                        <div key={group.key}>
                          {group.name ? (
                            <div className="mb-2 flex items-center justify-between gap-2">
                              <p className="text-xs font-semibold text-foreground">{group.name}</p>
                              {group.key !== 'unassigned' && meta.hasWings && (
                                <button
                                  type="button"
                                  onClick={() =>
                                    openFloorCreate(
                                      tower,
                                      wings.find((wing) => `wing-${wing.id}` === group.key),
                                    )
                                  }
                                  className="inline-flex items-center gap-1 text-[11px] font-semibold text-highlight transition hover:text-highlight/80"
                                >
                                  <PlusIcon className="h-3 w-3" />
                                  {meta.addFloorText}
                                </button>
                              )}
                            </div>
                          ) : null}
                          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {group.floors.map((floor) => (
                              <FloorCard
                                key={floor.id}
                                floor={floor}
                                meta={meta}
                                onManage={(item) =>
                                  setUnitsFloor({
                                    ...item,
                                    towerName: tower.name,
                                    structureMeta: meta,
                                  })
                                }
                                onEdit={(item) => openFloorEdit(tower, item)}
                                onDelete={(item) =>
                                  setDeleting({
                                    type: 'floor',
                                    id: item.id,
                                    name: meta.formatFloorName(item.floor_number),
                                  })
                                }
                              />
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </Card>
            )
          })}
        </div>
      )}

      {entityModal && (
        <EntityModal
          state={entityModal}
          error={entityError}
          loading={saveEntity.isPending}
          onClose={() => {
            setEntityModal(null)
            setEntityError('')
          }}
          onSubmit={(values) =>
            saveEntity.mutate({
              type: entityModal.type,
              itemId: entityModal.item?.id,
              towerId: entityModal.tower?.id,
              values,
            })
          }
        />
      )}

      {floorModal && (
        <FloorModal
          state={floorModal}
          error={floorError}
          loading={saveFloor.isPending}
          onClose={() => {
            setFloorModal(null)
            setFloorError('')
          }}
          onSubmit={(values) =>
            saveFloor.mutate({
              floor: floorModal.floor,
              towerId: floorModal.tower.id,
              wingId:
                floorModal.mode === 'create'
                  ? values.inventory_wing_id || floorModal.wing?.id || null
                  : null,
              payload: values,
            })
          }
        />
      )}

      <FloorUnitsModal
        open={Boolean(unitsFloor)}
        onClose={() => setUnitsFloor(null)}
        floor={unitsFloor}
        projectId={project.id}
      />

      <ConfirmDialog
        open={Boolean(deleting)}
        onClose={() => setDeleting(null)}
        onConfirm={confirmDelete}
        loading={deleteMutation.isPending}
        title={deleteDialog.title}
        description={deleteDialog.description}
        confirmLabel={deleteDialog.label}
      />
    </div>
  )
}
