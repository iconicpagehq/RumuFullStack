import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { CheckIcon } from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Modal } from '@/components/ui/Modal'
import { SearchInput } from '@/components/shared/SearchInput'
import { useEnterpriseReference } from '@/hooks/useEnterpriseReference'
import { getErrorMessage, serverFieldErrors } from '@/utils/errors'
import { roleLabel } from '@/utils/enterprise'
import { cn } from '@/utils/cn'

const flatten = (node, acc = new Map()) => {
  if (!node) return acc
  acc.set(node.membership_id, node)
  ;(node.children || []).forEach((child) => flatten(child, acc))
  return acc
}

export const MemberManagerModal = ({ open, onClose, membership }) => {
  const queryClient = useQueryClient()
  const { members } = useEnterpriseReference()
  const [search, setSearch] = useState('')
  const [managerId, setManagerId] = useState('')
  const [generalError, setGeneralError] = useState('')

  const isAdminMembership = membership?.role === 'admin'

  const treeQuery = useQuery({
    queryKey: ['enterprise', 'organization'],
    queryFn: async () => {
      const response = await enterpriseApi.organization.tree()
      return response.data?.data
    },
    enabled: open,
    staleTime: 60000,
  })

  useEffect(() => {
    if (!open) return
    setSearch('')
    setManagerId(
      membership?.reports_to_membership_id ? String(membership.reports_to_membership_id) : '',
    )
    setGeneralError('')
  }, [open, membership])

  const excludedIds = useMemo(() => {
    const nodes = new Map()
    ;(treeQuery.data?.roots || []).forEach((root) => flatten(root, nodes))
    const target = nodes.get(membership?.id)
    if (!target) return new Set([membership?.id].filter(Boolean))
    const ids = new Set()
    const walk = (node) => {
      ids.add(node.membership_id)
      ;(node.children || []).forEach(walk)
    }
    walk(target)
    return ids
  }, [treeQuery.data, membership])

  const candidates = useMemo(() => {
    const term = search.trim().toLowerCase()
    return members
      .filter(
        (member) =>
          member.status === 'active' &&
          member.user &&
          !excludedIds.has(member.id),
      )
      .filter((member) => {
        if (!term) return true
        return [member.user?.name, member.user?.email, member.designation, member.team?.name]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(term))
      })
      .sort((left, right) => (left.user?.name || '').localeCompare(right.user?.name || ''))
  }, [members, excludedIds, search])

  const mutation = useMutation({
    mutationFn: () =>
      enterpriseApi.members.changeManager(membership.id, {
        reports_to_membership_id: managerId ? Number(managerId) : null,
      }),
    onSuccess: () => {
      toast.success('Reporting line updated')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'member', membership.id] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'members'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'organization'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'teams'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'dashboard'] })
      onClose()
    },
    onError: (error) => {
      const fieldErrors = serverFieldErrors(error)
      if (fieldErrors.reports_to_membership_id) {
        setGeneralError(fieldErrors.reports_to_membership_id)
        return
      }
      setGeneralError(getErrorMessage(error, 'Could not update the reporting line.'))
    },
  })

  if (!membership) return null

  const memberName = membership.user?.name || 'This member'
  const canSave = isAdminMembership || Boolean(managerId)

  return (
    <Modal
      open={open}
      onClose={mutation.isPending ? () => {} : onClose}
      title="Change manager"
      description={`Choose who ${memberName} reports to.`}
      size="lg"
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button
            onClick={() => {
              setGeneralError('')
              mutation.mutate()
            }}
            loading={mutation.isPending}
            disabled={!canSave}
          >
            Save reporting line
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        {generalError && (
          <Alert variant="danger" onDismiss={() => setGeneralError('')}>
            {generalError}
          </Alert>
        )}

        <Alert variant="info">
          A member cannot report to themselves or to anyone in their own reporting line. Company
          admins always sit at the top of the organization.
        </Alert>

        {isAdminMembership ? (
          <p className="rounded-xl border border-border bg-muted/30 px-3 py-2.5 text-xs text-muted-foreground">
            {memberName} is a company admin and cannot report to anyone.
          </p>
        ) : (
          <>
            <SearchInput
              value={search}
              onChange={setSearch}
              placeholder="Search by name, email or team…"
            />

            <div className="max-h-80 space-y-1 overflow-y-auto rounded-xl border border-border p-1">
              {candidates.length === 0 ? (
                <p className="px-3 py-6 text-center text-xs text-muted-foreground">
                  {treeQuery.isLoading
                    ? 'Loading reporting line…'
                    : 'No eligible managers match this search.'}
                </p>
              ) : (
                candidates.map((member) => {
                  const selected = String(member.id) === String(managerId)
                  return (
                    <button
                      key={member.id}
                      type="button"
                      onClick={() => setManagerId(String(member.id))}
                      className={cn(
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition',
                        selected
                          ? 'bg-primary/10 ring-1 ring-inset ring-highlight/30'
                          : 'hover:bg-muted',
                      )}
                    >
                      <Avatar name={member.user?.name} size="sm" />
                      <span className="min-w-0 flex-1">
                        <span className="flex items-center gap-2">
                          <span className="truncate text-sm font-medium text-foreground">
                            {member.user?.name}
                          </span>
                          <Badge variant={member.role === 'manager' ? 'purple' : 'neutral'}>
                            {roleLabel(member.role)}
                          </Badge>
                        </span>
                        <span className="block truncate text-xs text-muted-foreground">
                          {[member.designation, member.team?.name, member.user?.email]
                            .filter(Boolean)
                            .join(' · ')}
                        </span>
                      </span>
                      {selected && <CheckIcon className="h-4 w-4 shrink-0 text-highlight" />}
                    </button>
                  )
                })
              )}
            </div>
          </>
        )}
      </div>
    </Modal>
  )
}
