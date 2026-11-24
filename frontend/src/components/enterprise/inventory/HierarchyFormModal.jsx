import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const titles = {
  tower: { create: 'Add tower', edit: 'Edit tower' },
  wing: { create: 'Add wing', edit: 'Edit wing' },
  floor: { create: 'Add floor', edit: 'Edit floor' },
}

export const HierarchyFormModal = ({ open, onClose, config }) => {
  const kind = config?.kind || 'tower'
  const isEdit = config?.mode === 'edit'
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: { name: '', sort_order: '', floor_number: '', inventory_wing_id: '' } })

  useEffect(() => {
    if (!open || !config) return
    reset({
      name: config.tower?.name || config.wing?.name || '',
      sort_order:
        config.tower?.sort_order !== undefined
          ? String(config.tower.sort_order)
          : config.wing?.sort_order !== undefined
            ? String(config.wing.sort_order)
            : '',
      floor_number:
        config.floor?.floor_number !== undefined ? String(config.floor.floor_number) : '',
      inventory_wing_id: config.floor?.inventory_wing_id
        ? String(config.floor.inventory_wing_id)
        : '',
    })
    setGeneralError('')
  }, [open, config, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      if (kind === 'floor') {
        const payload = { floor_number: Number(values.floor_number) }
        if (isEdit) return enterpriseApi.inventory.updateFloor(config.floor.id, payload)
        return enterpriseApi.inventory.addFloor(config.tower.id, {
          ...payload,
          inventory_wing_id: values.inventory_wing_id ? Number(values.inventory_wing_id) : null,
        })
      }

      const payload = {
        name: values.name.trim(),
        sort_order: values.sort_order === '' ? null : Number(values.sort_order),
      }
      if (kind === 'tower') {
        return isEdit
          ? enterpriseApi.inventory.updateTower(config.tower.id, payload)
          : enterpriseApi.inventory.addTower(config.projectId, payload)
      }
      return isEdit
        ? enterpriseApi.inventory.updateWing(config.wing.id, payload)
        : enterpriseApi.inventory.addWing(config.tower.id, payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Changes saved' : 'Created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', config.projectId] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (!applyServerErrors(error, setError, ['name', 'sort_order', 'floor_number', 'inventory_wing_id'])) {
        setGeneralError(getErrorMessage(error, 'Could not save this record.'))
      }
    },
  })

  const context =
    kind === 'wing' || (kind === 'floor' && config?.tower)
      ? config?.tower?.name
      : null

  const description = isEdit
    ? context
      ? `Update this ${kind} in ${context}.`
      : `Update this ${kind}.`
    : context
      ? `Add a ${kind} to ${context}.`
      : `Add a ${kind} to this project.`

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={titles[kind][isEdit ? 'edit' : 'create']}
      description={description}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-hierarchy-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-hierarchy-form"
        className="space-y-4"
        onSubmit={handleSubmit((values) => {
          setGeneralError('')
          mutation.mutate(values)
        })}
        noValidate
      >
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        {kind === 'floor' ? (
          <>
            <FormField
              label="Floor number"
              htmlFor="hierarchy-floor"
              error={errors.floor_number?.message}
              required
            >
              <Input
                id="hierarchy-floor"
                type="number"
                invalid={Boolean(errors.floor_number)}
                {...register('floor_number', {
                  required: 'Enter a floor number.',
                  validate: (value) => Number.isInteger(Number(value)) || 'Use a whole number.',
                })}
              />
            </FormField>

            {isEdit ? (
              <Alert variant="info">
                A floor&apos;s wing cannot be changed after creation. Its units remain attached.
              </Alert>
            ) : (
              <FormField
                label="Wing"
                htmlFor="hierarchy-wing"
                error={errors.inventory_wing_id?.message}
                hint="Optional. Create wings first if floors should be grouped by wing."
              >
                <Select id="hierarchy-wing" {...register('inventory_wing_id')}>
                  <option value="">No wing</option>
                  {(config?.wings || []).map((wing) => (
                    <option key={wing.id} value={wing.id}>
                      {wing.name}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
          </>
        ) : (
          <>
            <FormField
              label={`${kind === 'tower' ? 'Tower' : 'Wing'} name`}
              htmlFor="hierarchy-name"
              error={errors.name?.message}
              required
            >
              <Input
                id="hierarchy-name"
                placeholder={kind === 'tower' ? 'e.g. Tower A' : 'e.g. Wing East'}
                invalid={Boolean(errors.name)}
                {...register('name', { required: 'Enter a name.' })}
              />
            </FormField>
            <FormField
              label="Sort order"
              htmlFor="hierarchy-sort"
              error={errors.sort_order?.message}
              hint="Lower numbers appear first. Leave empty for 0."
            >
              <Input id="hierarchy-sort" type="number" min="0" {...register('sort_order')} />
            </FormField>
          </>
        )}
      </form>
    </Modal>
  )
}
