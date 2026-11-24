import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { useEnterprise } from '@/hooks/useEnterprise'
import { LeadSearchSelect } from '../LeadSearchSelect'
import { nextUnitStatuses, statusLabel } from '@/utils/inventory'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'

export const UnitStatusModal = ({ open, onClose, unit, projectId }) => {
  const queryClient = useQueryClient()
  const { isAdmin } = useEnterprise()
  const [status, setStatus] = useState('')
  const [leadId, setLeadId] = useState('')
  const [note, setNote] = useState('')
  const [generalError, setGeneralError] = useState('')

  const options = nextUnitStatuses(unit?.availability_status)

  useEffect(() => {
    if (!open) return
    setStatus('')
    setLeadId('')
    setNote('')
    setGeneralError('')
    // options is derived from the selected unit
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, unit?.id])

  const mutation = useMutation({
    mutationFn: () =>
      enterpriseApi.inventory.updateUnitStatus(unit.id, {
        availability_status: status,
        lead_id: ['hold', 'booked', 'sold'].includes(status) && leadId ? Number(leadId) : null,
        note: note.trim() || null,
      }),
    onSuccess: () => {
      toast.success(`Unit marked ${statusLabel(status).toLowerCase()}`)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-project', projectId] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'inventory-projects'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      if (leadId) queryClient.invalidateQueries({ queryKey: ['enterprise', 'lead', Number(leadId)] })
      onClose()
    },
    onError: (error) => {
      const applied = applyServerErrors(error, (field, value) => {
        if (field === 'availability_status') setGeneralError(value.message)
        if (field === 'lead_id') setGeneralError(value.message)
        if (field === 'note') setGeneralError(value.message)
      }, ['availability_status', 'lead_id', 'note'])
      if (!applied) setGeneralError(getErrorMessage(error, 'Could not update this unit.'))
    },
  })

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Update unit status"
      description={unit?.full_path || (unit ? `Unit ${unit.unit_number}` : '')}
      footer={
        options.length === 0 ? (
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
        ) : (
          <>
            <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
              Cancel
            </Button>
            <Button onClick={() => mutation.mutate()} loading={mutation.isPending} disabled={!status}>
              Update status
            </Button>
          </>
        )
      }
    >
      <div className="space-y-4">
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        <div className="flex items-center justify-between rounded-xl border border-border bg-muted/40 px-4 py-3">
          <span className="text-xs font-medium text-muted-foreground">Current status</span>
          <StatusBadge status={unit?.availability_status} />
        </div>

        {options.length === 0 ? (
          <Alert variant="info" title="Sold units are final">
            This unit has been sold. Inventory status cannot be changed after a sale. Create a new unit
            if this sale is reversed and the unit becomes available again.
          </Alert>
        ) : (
          <>
            <FormField label="New status" htmlFor="unit-status">
              <div className="grid gap-2 sm:grid-cols-2">
                {options.map((option) => (
                  <button
                    key={option}
                    type="button"
                    onClick={() => setStatus(option)}
                    className={`flex items-center justify-between rounded-xl border px-3.5 py-3 text-left transition ${
                      status === option
                        ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                        : 'border-border hover:bg-muted/60'
                    }`}
                  >
                    <span className="text-sm font-medium text-foreground">{statusLabel(option)}</span>
                    <span
                      className={`h-2.5 w-2.5 rounded-full ${
                        status === option ? 'bg-primary' : 'bg-muted-foreground/30'
                      }`}
                      aria-hidden="true"
                    />
                  </button>
                ))}
              </div>
            </FormField>

            {['hold', 'booked', 'sold'].includes(status) && (
              <FormField
                label="Buyer / lead"
                error={null}
                hint={
                  isAdmin
                    ? 'Optional for admins. Linking a lead ties this unit to the customer record.'
                    : 'Required when reserving a unit for your team or an assigned lead.'
                }
              >
                <LeadSearchSelect id="unit-lead" value={leadId} onChange={(event) => setLeadId(event.target.value)} />
              </FormField>
            )}

            {['available', 'hold'].includes(status) && unit?.availability_status === 'booked' && (
              <Alert variant="warning">
                Changing a booked unit to {statusLabel(status).toLowerCase()} removes its linked lead.
                The lead record itself is kept.
              </Alert>
            )}

            <FormField label="Note" htmlFor="unit-status-note">
              <Textarea
                id="unit-status-note"
                rows={2}
                value={note}
                onChange={(event) => setNote(event.target.value)}
                placeholder="Reason or reference for this change…"
              />
            </FormField>
          </>
        )}
      </div>
    </Modal>
  )
}
