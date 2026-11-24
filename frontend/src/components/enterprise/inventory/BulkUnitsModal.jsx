import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { INVENTORY_LAYOUTS } from '@/utils/inventory'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

const defaults = {
  prefix: '',
  start_number: '1',
  end_number: '10',
  layout: '',
  carpet_area: '',
  built_up_area: '',
  base_price: '',
}

export const BulkUnitsModal = ({ open, onClose, floor, projectId }) => {
  const queryClient = useQueryClient()
  const [generalError, setGeneralError] = useState('')

  const {
    register,
    handleSubmit,
    reset,
    setError,
    watch,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open) return
    reset(defaults)
    setGeneralError('')
  }, [open, reset])

  const start = Number(watch('start_number'))
  const end = Number(watch('end_number'))
  const count =
    Number.isInteger(start) && Number.isInteger(end) && end >= start && start >= 1 ? end - start + 1 : 0

  const mutation = useMutation({
    mutationFn: (values) =>
      enterpriseApi.inventory.bulkUnits(floor.id, {
        prefix: values.prefix?.trim() || null,
        start_number: Number(values.start_number),
        end_number: Number(values.end_number),
        layout: values.layout || null,
        carpet_area: values.carpet_area === '' ? null : Number(values.carpet_area),
        built_up_area: values.built_up_area === '' ? null : Number(values.built_up_area),
        base_price: values.base_price === '' ? null : Number(values.base_price),
      }),
    onSuccess: (response) => {
      const created = response.data?.data?.created ?? count
      toast.success(`${created} unit${created === 1 ? '' : 's'} created`)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', projectId] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'prefix',
          'start_number',
          'end_number',
          'layout',
          'carpet_area',
          'built_up_area',
          'base_price',
          'limit',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not create units.'))
      }
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Bulk add units"
      description={floor?.label ? `Generate numbered units on ${floor.label}.` : 'Generate numbered units.'}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button
            type="submit"
            form="enterprise-bulk-unit-form"
            loading={mutation.isPending}
            disabled={count === 0 || count > 500}
          >
            Create {count > 0 && count <= 500 ? `${count} units` : 'units'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-bulk-unit-form"
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

        <div className="grid gap-4 sm:grid-cols-3">
          <FormField label="Prefix" htmlFor="bulk-prefix" error={errors.prefix?.message}>
            <Input id="bulk-prefix" placeholder="e.g. A-" {...register('prefix')} />
          </FormField>
          <FormField label="Start number" htmlFor="bulk-start" error={errors.start_number?.message} required>
            <Input
              id="bulk-start"
              type="number"
              min="1"
              invalid={Boolean(errors.start_number)}
              {...register('start_number', {
                required: 'Required',
                validate: (value) => Number(value) >= 1 || 'Must be 1 or more',
              })}
            />
          </FormField>
          <FormField label="End number" htmlFor="bulk-end" error={errors.end_number?.message} required>
            <Input
              id="bulk-end"
              type="number"
              min="1"
              invalid={Boolean(errors.end_number)}
              {...register('end_number', {
                required: 'Required',
                validate: (value) =>
                  Number(value) >= Number(watch('start_number')) || 'Must be at least the start number',
              })}
            />
          </FormField>
        </div>

        <p className="text-xs text-muted-foreground">
          {count > 500
            ? 'A batch can create at most 500 units. Reduce the range.'
            : count > 0
              ? `This will create ${count} unit${count === 1 ? '' : 's'}. Existing unit numbers are skipped.`
              : 'Enter a valid range to preview the batch.'}
        </p>

        <FormField label="Layout" htmlFor="bulk-layout" error={errors.layout?.message}>
          <Select id="bulk-layout" {...register('layout')}>
            <option value="">Not set</option>
            {INVENTORY_LAYOUTS.map((layout) => (
              <option key={layout.value} value={layout.value}>
                {layout.label}
              </option>
            ))}
          </Select>
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Carpet area (sq ft)" htmlFor="bulk-carpet" error={errors.carpet_area?.message}>
            <Input id="bulk-carpet" type="number" min="0" step="0.01" {...register('carpet_area')} />
          </FormField>
          <FormField label="Built-up area (sq ft)" htmlFor="bulk-built" error={errors.built_up_area?.message}>
            <Input id="bulk-built" type="number" min="0" step="0.01" {...register('built_up_area')} />
          </FormField>
        </div>

        <FormField label="Base price (₹)" htmlFor="bulk-price" error={errors.base_price?.message}>
          <Input id="bulk-price" type="number" min="0" step="1000" {...register('base_price')} />
        </FormField>
      </form>
    </Modal>
  )
}
