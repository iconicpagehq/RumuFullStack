import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import {
  ClipboardDocumentIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  UserPlusIcon,
  XCircleIcon,
} from '@heroicons/react/20/solid'
import { enterpriseApi } from '@/api/enterprise'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { SearchInput } from '@/components/shared/SearchInput'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { EmptyState } from '@/components/ui/EmptyState'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { InviteMemberModal } from '@/components/enterprise/InviteMemberModal'
import { useDebounce } from '@/hooks/useDebounce'
import { useEnterprise } from '@/hooks/useEnterprise'
import { PAGE_SIZE } from '@/utils/constants'
import { daysLeft, roleLabel } from '@/utils/enterprise'
import { formatDate, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const TABS = [
  { key: '', label: 'All' },
  { key: 'pending', label: 'Pending' },
  { key: 'accepted', label: 'Accepted' },
  { key: 'expired', label: 'Expired' },
  { key: 'revoked', label: 'Revoked' },
]

const copyToClipboard = async (value) => {
  try {
    await navigator.clipboard.writeText(value)
    toast.success('Invitation link copied')
  } catch {
    toast.error('Could not copy automatically. Select the link and copy it manually.')
  }
}

export const InvitationsPage = () => {
  const queryClient = useQueryClient()
  const { isAdmin } = useEnterprise()
  const [status, setStatus] = useState('')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [inviteOpen, setInviteOpen] = useState(false)
  const [revokeTarget, setRevokeTarget] = useState(null)
  const [resent, setResent] = useState(null)
  const debouncedSearch = useDebounce(search)

  useEffect(() => {
    setPage(1)
  }, [debouncedSearch, status])

  const { data, isLoading, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['enterprise', 'invitations', { status, search: debouncedSearch, page }],
    queryFn: async () => {
      const response = await enterpriseApi.invitations.list({
        status: status || undefined,
        search: debouncedSearch || undefined,
        page,
      })
      return { rows: response.data?.data || [], meta: response.data?.meta || {} }
    },
    placeholderData: (previous) => previous,
    enabled: isAdmin,
  })

  const resendMutation = useMutation({
    mutationFn: (invitation) => enterpriseApi.invitations.resend(invitation.id),
    onSuccess: (response) => {
      const invitation = response.data?.data
      toast.success('Invitation resent')
      setResent(invitation)
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'invitations'] })
    },
    onError: (resendError) => {
      toast.error(getErrorMessage(resendError, 'Could not resend this invitation.'))
    },
  })

  const revokeMutation = useMutation({
    mutationFn: (invitation) => enterpriseApi.invitations.revoke(invitation.id),
    onSuccess: () => {
      toast.success('Invitation revoked')
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'invitations'] })
      queryClient.invalidateQueries({ queryKey: ['enterprise', 'reference'] })
      setRevokeTarget(null)
    },
    onError: (revokeError) => {
      toast.error(getErrorMessage(revokeError, 'Could not revoke this invitation.'))
      setRevokeTarget(null)
    },
  })

  if (!isAdmin) {
    return (
      <div>
        <PageHeader title="Invitations" description="Manage enterprise invitations." />
        <EmptyState
          icon={EnvelopeIcon}
          title="Admin access required"
          description="Only company administrators can invite or manage team members."
        />
      </div>
    )
  }

  if (isError) return <ErrorState error={error} onRetry={refetch} />

  const invitations = data?.rows || []
  const meta = data?.meta || {}

  const columns = [
    {
      key: 'email',
      header: 'Invitee',
      render: (invitation) => (
        <div className="min-w-0">
          <p className="truncate text-sm font-medium text-foreground">{invitation.email}</p>
          <p className="truncate text-xs text-muted-foreground">
            {invitation.invited_by?.name ? `Invited by ${invitation.invited_by.name}` : 'Invited by admin'}
          </p>
        </div>
      ),
    },
    {
      key: 'role',
      header: 'Role',
      render: (invitation) => (
        <Badge variant={invitation.role === 'manager' ? 'purple' : 'neutral'}>
          {roleLabel(invitation.role)}
        </Badge>
      ),
    },
    {
      key: 'team',
      header: 'Team',
      render: (invitation) => (
        <span className="text-xs text-foreground">{invitation.team?.name || 'No team'}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (invitation) => <StatusBadge status={invitation.status} />,
    },
    {
      key: 'expires',
      header: 'Expires',
      render: (invitation) => {
        if (invitation.status === 'accepted') {
          return (
            <span className="text-xs text-muted-foreground">
              Accepted {formatDate(invitation.accepted_at)}
            </span>
          )
        }
        if (invitation.status === 'revoked') {
          return (
            <span className="text-xs text-muted-foreground">
              Revoked {formatDate(invitation.revoked_at)}
            </span>
          )
        }
        if (invitation.status === 'expired') {
          return <span className="text-xs text-muted-foreground">Expired {formatDate(invitation.expires_at)}</span>
        }
        const remaining = daysLeft(invitation.expires_at)
        return (
          <div className="text-xs">
            <p className="text-foreground">{formatDate(invitation.expires_at)}</p>
            <p className="text-muted-foreground">
              {remaining === 0 ? 'Expires today' : `${remaining} day${remaining === 1 ? '' : 's'} left`}
            </p>
          </div>
        )
      },
    },
    {
      key: 'created_at',
      header: 'Sent',
      render: (invitation) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
          {formatDate(invitation.created_at)}
        </span>
      ),
    },
    {
      key: 'actions',
      header: '',
      headerClassName: 'text-right',
      className: 'text-right',
      stopPropagation: true,
      render: (invitation) =>
        invitation.status === 'pending' ? (
          <div className="flex justify-end gap-1">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => resendMutation.mutate(invitation)}
              loading={resendMutation.isPending && resendMutation.variables?.id === invitation.id}
            >
              <PaperAirplaneIcon className="h-4 w-4" />
              Resend
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setRevokeTarget(invitation)}
              loading={revokeMutation.isPending && revokeMutation.variables?.id === invitation.id}
            >
              <XCircleIcon className="h-4 w-4" />
              Revoke
            </Button>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">—</span>
        ),
    },
  ]

  return (
    <div>
      <PageHeader
        title="Invitations"
        description="Invite managers and employees, resend secure links or revoke unused invitations."
        actions={
          <Button onClick={() => setInviteOpen(true)}>
            <UserPlusIcon className="h-4 w-4" />
            Invite member
          </Button>
        }
      />

      <Card>
        <CardContent className="px-0 pb-0 pt-4">
          <div className="flex flex-col gap-3 px-5 lg:flex-row lg:items-center lg:justify-between">
            <Tabs tabs={TABS} value={status} onChange={setStatus} className="border-0" />
            <div className="flex items-center gap-3">
              <p className="hidden text-xs text-muted-foreground sm:block">
                {formatNumber(meta.total || 0)} invitation{meta.total === 1 ? '' : 's'}
                {isFetching && <span className="ml-2">Refreshing…</span>}
              </p>
              <SearchInput
                value={search}
                onChange={setSearch}
                placeholder="Search email…"
                className="w-full sm:w-64"
              />
            </div>
          </div>

          <div className="mt-4 border-t border-border">
            <DataTable
              className="rounded-none border-0"
              columns={columns}
              rows={invitations}
              loading={isLoading}
              emptyState={
                <EmptyState
                  icon={EnvelopeIcon}
                  title={search || status ? 'No matching invitations' : 'No invitations yet'}
                  description={
                    search || status
                      ? 'Try a different status or email search.'
                      : 'Invite your first manager or employee to start building the team.'
                  }
                  action={
                    !search && !status ? (
                      <Button onClick={() => setInviteOpen(true)}>
                        <UserPlusIcon className="h-4 w-4" />
                        Invite member
                      </Button>
                    ) : null
                  }
                />
              }
            />
          </div>

          <div className="border-t border-border px-4">
            <Pagination
              page={meta.current_page || 1}
              lastPage={meta.last_page || 1}
              total={meta.total || 0}
              perPage={meta.per_page || PAGE_SIZE}
              onPageChange={setPage}
            />
          </div>
        </CardContent>
      </Card>

      <InviteMemberModal open={inviteOpen} onClose={() => setInviteOpen(false)} />

      <Modal
        open={Boolean(resent)}
        onClose={() => setResent(null)}
        title="Invitation resent"
        description="A fresh 7-day invitation email has been sent."
        footer={<Button onClick={() => setResent(null)}>Done</Button>}
      >
        <div className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Share this link with <span className="font-medium text-foreground">{resent?.email}</span> if the
            email does not arrive.
          </p>
          <div className="flex gap-2">
            <Input id="resent-invite-link" readOnly value={resent?.invite_url || ''} className="text-xs" />
            <Button
              variant="outline"
              onClick={() => copyToClipboard(resent?.invite_url || '')}
              disabled={!resent?.invite_url}
            >
              <ClipboardDocumentIcon className="h-4 w-4" />
              Copy
            </Button>
          </div>
        </div>
      </Modal>

      <ConfirmDialog
        open={Boolean(revokeTarget)}
        onClose={() => setRevokeTarget(null)}
        onConfirm={() => revokeMutation.mutate(revokeTarget)}
        loading={revokeMutation.isPending}
        title={`Revoke the invitation for ${revokeTarget?.email || 'this person'}?`}
        description="The secure link will stop working immediately. You can send a new invitation later."
        confirmLabel="Revoke invitation"
      />
    </div>
  )
}
