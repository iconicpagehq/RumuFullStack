import { useEffect, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { FormField, Input, Select } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { useEnterprise } from '@/hooks/useEnterprise'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { TARGET_METRICS, metricFormat, metricLabel } from '@/utils/enterprise'
import { applyServerErrors, getErrorMessage } from '@/utils/errors'
import { formatCurrency, formatNumber } from '@/utils/formatters'

const defaults = { period_month: '', metric: 'leads_assigned', target_value: '', team_id: '', user_id: '' }

export const TargetFormModal = ({ open, onClose, target = null, month }) => {
  const isEdit = Boolean(target?.id)
  const queryClient = useQueryClient()
  const { isAdmin, isManager } = useEnterprise()
  const { teams, assignableMembers } = useEnterpriseReference()
  const [generalError, setGeneralError] = useState('')
  const [scope, setScope] = useState('enterprise')

  const {
    register,
    handleSubmit,
    reset,
    setValue,
    watch,
    setError,
    formState: { errors },
  } = useForm({ defaultValues: defaults })

  useEffect(() => {
    if (!open) return
    const initialScope = target?.user_id ? 'member' : target?.team_id ? 'team' : 'enterprise'
    const fallbackTeam = isManager ? teams[0]?.id : null
    reset({
      period_month: target?.period_month || month || '',
      metric: target?.metric || 'leads_assigned',
      target_value:
        target?.target_value !== null && target?.target_value !== undefined
          ? String(target.target_value)
          : '',
      team_id: target?.team_id ? String(target.team_id) : fallbackTeam ? String(fallbackTeam) : '',
      user_id: target?.user_id ? String(target.user_id) : '',
    })
    setScope(isManager ? 'team' : initialScope)
    setGeneralError('')
  }, [open, target, month, isManager, teams, reset])

  const metric = watch('metric')
  const teamId = watch('team_id')
  const memberOptions = assignableMembers.filter(
    (member) => !teamId || String(member.team?.id) === String(teamId),
  )

  const mutation = useMutation({
    mutationFn: (values) => {
      if (isEdit) {
        return enterpriseApi.targets.update(target.id, {
          target_value: Number(values.target_value),
        })
      }
      return enterpriseApi.targets.create({
        period_month: values.period_month,
        metric: values.metric,
        target_value: Number(values.target_value),
        team_id: scope === 'team' && values.team_id ? Number(values.team_id) : null,
        user_id: scope === 'member' && values.user_id ? Number(values.user_id) : null,
      })
    },
    onSuccess: () => {
      toast.success(isEdit ? 'Target updated' : 'Target created')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'targets'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      if (
        !applyServerErrors(error, setError, [
          'period_month',
          'metric',
          'target_value',
          'team_id',
          'user_id',
        ])
      ) {
        setGeneralError(getErrorMessage(error, 'Could not save this target.'))
      }
    },
  })

  const changeScope = (value) => {
    setScope(value)
    if (value !== 'team') setValue('team_id', '')
    if (value !== 'member') setValue('user_id', '')
  }

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title={isEdit ? 'Edit target' : 'Set a sales target'}
      description={
        isEdit
          ? `${metricLabel(target.metric)} · ${target.period_month}`
          : 'Create one target per metric, scope and month.'
      }
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="enterprise-target-form" loading={mutation.isPending}>
            {isEdit ? 'Save target' : 'Create target'}
          </Button>
        </>
      }
    >
      <form
        id="enterprise-target-form"
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

        {isEdit ? (
          <div className="grid gap-4 sm:grid-cols-2">
            <FormField label="Metric">
              <Input value={metricLabel(target.metric)} disabled readOnly />
            </FormField>
            <FormField label="Scope">
              <Input
                value={
                  target.user?.name ||
                  target.team?.name ||
                  'Enterprise'
                }
                disabled
                readOnly
              />
            </FormField>
          </div>
        ) : (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              <FormField label="Month" htmlFor="target-month" error={errors.period_month?.message} required>
                <Input
                  id="target-month"
                  type="month"
                  invalid={Boolean(errors.period_month)}
                  {...register('period_month', { required: 'Choose a month.' })}
                />
              </FormField>
              <FormField label="Metric" htmlFor="target-metric" error={errors.metric?.message} required>
                <Select id="target-metric" invalid={Boolean(errors.metric)} {...register('metric')}>
                  {TARGET_METRICS.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </Select>
              </FormField>
            </div>

            {(isAdmin || isManager) && (
              <FormField
                label="Target scope"
                htmlFor="target-scope"
                hint={
                  isManager
                    ? 'A team you manage, or one of its members.'
                    : 'Company-wide, one team, or one team member.'
                }
              >
                <Select
                  id="target-scope"
                  value={scope}
                  onChange={(event) => changeScope(event.target.value)}
                >
                  {isAdmin && <option value="enterprise">Company-wide</option>}
                  <option value="team">Team</option>
                  <option value="member">Team member</option>
                </Select>
              </FormField>
            )}

            {(isManager || scope === 'team') && (
              <FormField label="Team" htmlFor="target-team" error={errors.team_id?.message} required>
                <Select
                  id="target-team"
                  invalid={Boolean(errors.team_id)}
                  {...register('team_id', {
                    validate: (value) =>
                      !(isManager || scope === 'team') || Boolean(value) || 'Select a team.',
                  })}
                >
                  <option value="">Select a team</option>
                  {teams.map((team) => (
                    <option key={team.id} value={team.id}>
                      {team.name}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}

            {(isAdmin || isManager) && scope === 'member' && (
              <FormField
                label="Team member"
                htmlFor="target-user"
                error={errors.user_id?.message}
                hint={isManager ? 'Only active members of the selected team.' : undefined}
                required
              >
                <Select
                  id="target-user"
                  invalid={Boolean(errors.user_id)}
                  {...register('user_id', {
                    validate: (value) => scope !== 'member' || Boolean(value) || 'Select a member.',
                  })}
                >
                  <option value="">Select a member</option>
                  {memberOptions.map((member) => (
                    <option key={member.user.id} value={member.user.id}>
                      {member.user.name} · {member.role}
                    </option>
                  ))}
                </Select>
              </FormField>
            )}
          </>
        )}

        <FormField
          label="Target value"
          htmlFor="target-value"
          error={errors.target_value?.message}
          required
          hint={
            metricFormat(metric) === 'currency'
              ? 'Enter an amount in rupees.'
              : 'Enter a count for the month.'
          }
        >
          <Input
            id="target-value"
            type="number"
            min="0"
            step={metricFormat(metric) === 'currency' ? '1000' : '1'}
            invalid={Boolean(errors.target_value)}
            {...register('target_value', {
              required: 'Enter a target value.',
              validate: (value) => Number(value) >= 0 || 'Target cannot be negative.',
            })}
          />
        </FormField>

        {isEdit && (
          <p className="text-xs text-muted-foreground">
            Current actual: {formatValue(target.actual_value, target.metric)}
          </p>
        )}

        {metric === 'revenue_received' && (
          <Alert variant="info">
            Revenue received is tracked as a target metric, but actual received revenue is not yet
            reconciled from payments in this workspace.
          </Alert>
        )}
      </form>
    </Modal>
  )
}

const formatValue = (value, metric) =>
  metricFormat(metric) === 'currency' ? formatCurrency(value) : formatNumber(value)
