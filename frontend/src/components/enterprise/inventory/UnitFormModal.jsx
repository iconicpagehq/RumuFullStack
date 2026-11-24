import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { INVENTORY_LAYOUTS } from '@/utils/inventory'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = {
  unit_number: '',
  layout: '',
  carpet_area: '',
  built_up_area: '',
  facing: '',
  base_price: '',
  notes: '',
}

export const UnitFormModal = ({ open, onClose, floor, unit = null, projectId }) => {
  const isEdit = Boolean(unit?.id)
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open) return
    reset({
      unit_number: unit?.unit_number || '',
      layout: unit?.layout || '',
      carpet_area: unit?.carpet_area !== null && unit?.carpet_area !== undefined ? String(unit.carpet_area) : '',
      built_up_area:
        unit?.built_up_area !== null && unit?.built_up_area !== undefined ? String(unit.built_up_area) : '',
      facing: unit?.facing || '',
      base_price: unit?.base_price !== null && unit?.base_price !== undefined ? String(unit.base_price) : '',
      notes: '',
    })
    setGeneralError('')
  }, [open, unit, reset])

  const mutation = useMutation({
    mutationFn: (values) => {
      const payload = {
        unit_number: values.unit_number.trim(),
        layout: values.layout || null,
        carpet_area: values.carpet_area === '' ? null : Number(values.carpet_area),
        built_up_area: values.built_up_area === '' ? null : Number(values.built_up_area),
        facing: values.facing?.trim() || null,
        base_price: values.base_price === '' ? null : Number(values.base_price),
      }
      if (!isEdit) payload.notes = values.notes?.trim() || null
      return isEdit
        ? enterpriseApi.inventory.updateUnit(unit.id, payload)
        : enterpriseApi.inventory.addUnit(floor.id, payload)
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Unit updated' : 'Unit added')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', projectId] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'unit_number',
          'layout',
          'carpet_area',
          'built_up_area',
          'facing',
          'base_price',
          'notes',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this unit.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit unit' : 'Add unit'}
      description={
        isEdit
          ? `${unit?.full_path || `Unit ${unit?.unit_number}`}`
          : floor?.label
            ? `Add a unit to ${floor.label}. New units start as available.`
            : 'New units start as available.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-unit-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Add unit'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-unit-form"
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

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Unit number" htmlFor="unit-number" error={errors.unit_number?.message} required>
            <Input
              id="unit-number"
              placeholder="e.g. A-1201"
              invalid={Boolean(errors.unit_number)}
              {...register('unit_number', { required: 'Enter a unit number.' })}
            />
          </FormField>
          <FormField label="Layout" htmlFor="unit-layout" error={errors.layout?.message}>
            <Select id="unit-layout" {...register('layout')}>
              <option value="">Not set</option>
              {INVENTORY_LAYOUTS.map((layout) => (
                <option key={layout.value} value={layout.value}>
                  {layout.label}
                </option>
              ))}
            </Select>
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Carpet area (sq ft)" htmlFor="unit-carpet" error={errors.carpet_area?.message}>
            <Input id="unit-carpet" type="number" min="0" step="0.01" {...register('carpet_area')} />
          </FormField>
          <FormField label="Built-up area (sq ft)" htmlFor="unit-built" error={errors.built_up_area?.message}>
            <Input id="unit-built" type="number" min="0" step="0.01" {...register('built_up_area')} />
          </FormField>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Facing" htmlFor="unit-facing" error={errors.facing?.message}>
            <Input id="unit-facing" placeholder="e.g. East" {...register('facing')} />
          </FormField>
          <FormField label="Base price (₹)" htmlFor="unit-price" error={errors.base_price?.message}>
            <Input id="unit-price" type="number" min="0" step="1000" {...register('base_price')} />
          </FormField>
        </div>

        {!isEdit && (
          <FormField label="Internal notes" htmlFor="unit-notes" error={errors.notes?.message}>
            <Textarea id="unit-notes" rows={2} {...register('notes')} />
          </FormField>
        )}
      </form>
    </Modal>
  )
}
