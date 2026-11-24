import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { ArrowTrendingUpIcon, UserPlusIcon } from '@heroicons/react/20/solid'
import { adminApi } from '@/api/admin'
import { Avatar } from '@/components/ui/Avatar'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { FormField, Select, Textarea } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Pagination } from '@/components/ui/Pagination'
import { Tabs } from '@/components/ui/Tabs'
import { DataTable } from '@/components/shared/DataTable'
import { ErrorState } from '@/components/shared/ErrorState'
import { PageHeader } from '@/components/shared/PageHeader'
import { StatusBadge } from '@/components/shared/StatusBadge'
import { PAGE_SIZE } from '@/utils/constants'
import { formatDate, formatDateTime, formatNumber } from '@/utils/formatters'
import { getErrorMessage } from '@/utils/errors'

const requestedPlanOf = (request) => request?.requested_plan || request?.requestedPlan
const currentPlanOf = (request) => request?.current_plan || request?.currentPlan

const TABS = [
  { key: 'plans', label: 'Plan upgrades' },
  { key: 'seats', label: 'Seat requests' },
]

export const UpgradeRequestsPage = () => {
  const queryClient = useQueryClient()
  const [tab, setTab] = useState('plans')
  const [page, setPage] = useState(1)
  const [seatStatus, setSeatStatus] = useState('pending')
  const [dialog, setDialog] = useState(null)
  const [notes, setNotes] = useState('')
  const [error, setError] = useState('')

  const planQuery = useQuery({
    queryKey: ['admin', 'upgrade-requests', page],
    queryFn: async () => {
      const response = await adminApi.upgradeRequests.list({ page, per_page: PAGE_SIZE })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    enabled: tab === 'plans',
    placeholderData: (previous) => previous,
  })

  const seatQuery = useQuery({
    queryKey: ['admin', 'seat-requests', { status: seatStatus, page }],
    queryFn: async () => {
      const response = await adminApi.seatRequests.list({
        status: seatStatus || undefined,
        page,
        per_page: PAGE_SIZE,
      })
      return {
        rows: response.data?.data || [],
        meta: response.data?.meta || {},
      }
    },
    enabled: tab === 'seats',
    placeholderData: (previous) => previous,
  })

  const processMutation = useMutation({
    mutationFn: ({ type, request, payload }) => {
      if (dialog?.kind === 'seat') {
        return type === 'approve'
          ? adminApi.seatRequests.approve(request.id, payload)
          : adminApi.seatRequests.reject(request.id, payload)
      }
      return type === 'approve'
        ? adminApi.upgradeRequests.approve(request.id, payload)
        : adminApi.upgradeRequests.reject(request.id, payload)
    },
    onSuccess: (_, variables) => {
      toast.success(
        variables.type === 'approve'
          ? dialog?.kind === 'seat'
            ? 'Seat request approved'
            : 'Upgrade approved'
          : 'Request rejected',
      )
      setDialog(null)
      setNotes('')
      setError('')
      queryClient.invalidateQueries({ queryKey: ['admin', 'upgrade-requests'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'seat-requests'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'enterprises'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] })
      queryClient.invalidateQueries({ queryKey: ['admin', 'realtors'] })
    },
    onError: (err) => {
      const message = getErrorMessage(err, 'Could not process the request.')
      setError(message)
      toast.error(message)
    },
  })

  const openDialog = (kind, type, request) => {
    setDialog({ kind, type, request })
    setNotes('')
    setError('')
  }

  const submit = () => {
    if (!dialog) return
    if (dialog.type === 'reject' && !notes.trim()) {
      setError('A rejection reason is required.')
      return
    }
    const payload =
      dialog.type === 'approve' ? { notes: notes.trim() || undefined } : { reason: notes.trim() }
    processMutation.mutate({ type: dialog.type, request: dialog.request, payload })
  }

  const switchTab = (key) => {
    setTab(key)
    setPage(1)
    setSeatStatus('pending')
  }

  const planColumns = [
    {
      key: 'user',
      header: 'Realtor',
      render: (request) => (
        <div className="flex min-w-[200px] items-center gap-3">
          <Avatar src={request.user?.avatar_url} name={request.user?.name} size="sm" />
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">{request.user?.name || 'Unknown'}</p>
            <p className="truncate text-xs text-muted-foreground">{request.user?.email || '—'}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'plans',
      header: 'Plan change',
      render: (request) => (
        <div className="flex items-center gap-2 text-sm">
          <span className="text-muted-foreground">{currentPlanOf(request)?.name || 'No plan'}</span>
          <span className="text-muted-foreground">→</span>
          <span className="font-semibold text-foreground">{requestedPlanOf(request)?.name || 'Unknown'}</span>
        </div>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (request) => <StatusBadge status={request.status} />,
    },
    {
      key: 'created_at',
      header: 'Requested',
      render: (request) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDateTime(request.created_at)}</span>
      ),
    },
    {
      key: 'notes',
      header: 'Admin notes',
      render: (request) => (
        <span className="block max-w-[220px] truncate text-xs text-muted-foreground" title={request.admin_notes || ''}>
          {request.admin_notes || '—'}
        </span>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (request) =>
        request.status === 'pending' ? (
          <div className="flex justify-end gap-2">
            <Button size="sm" variant="outline" onClick={() => openDialog('plan', 'reject', request)}>
              Reject
            </Button>
            <Button size="sm" onClick={() => openDialog('plan', 'approve', request)}>
              Approve
            </Button>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">
            {request.approved_at ? formatDate(request.approved_at) : '—'}
          </span>
        ),
    },
  ]

  const seatColumns = [
    {
      key: 'enterprise',
      header: 'Enterprise',
      render: (request) => (
        <div className="min-w-[160px]">
          <p className="truncate text-sm font-semibold text-foreground">{request.enterprise?.name || 'Unknown'}</p>
          <p className="truncate text-xs text-muted-foreground">
            {request.requested_by?.name || '—'} {request.requested_by?.email ? `· ${request.requested_by.email}` : ''}
          </p>
        </div>
      ),
    },
    {
      key: 'seats',
      header: 'Seats',
      render: (request) => (
        <div className="flex items-center gap-2">
          <Badge variant="primary">
            +{formatNumber(request.quantity || 0)} {request.seat_label || 'seats'}
          </Badge>
        </div>
      ),
    },
    {
      key: 'note',
      header: 'Reason',
      render: (request) => (
        <span className="block max-w-[220px] truncate text-xs text-muted-foreground" title={request.note || ''}>
          {request.note || '—'}
        </span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      render: (request) => <StatusBadge status={request.status} />,
    },
    {
      key: 'created_at',
      header: 'Requested',
      render: (request) => (
        <span className="whitespace-nowrap text-xs text-muted-foreground">{formatDateTime(request.created_at)}</span>
      ),
    },
    {
      key: 'admin_notes',
      header: 'Admin notes',
      render: (request) => (
        <span className="block max-w-[180px] truncate text-xs text-muted-foreground" title={request.admin_notes || ''}>
          {request.admin_notes || '—'}
        </span>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      stopPropagation: true,
      render: (request) =>
        request.status === 'pending' ? (
          <div className="flex justify-end gap-2">
            <Button size="sm" variant="outline" onClick={() => openDialog('seat', 'reject', request)}>
              Reject
            </Button>
            <Button size="sm" onClick={() => openDialog('seat', 'approve', request)}>
              Approve
            </Button>
          </div>
        ) : (
          <span className="text-xs text-muted-foreground">
            {request.approved_at ? formatDate(request.approved_at) : '—'}
            {request.approved_by ? ` · ${request.approved_by}` : ''}
          </span>
        ),
    },
  ]

  const activeQuery = tab === 'plans' ? planQuery : seatQuery
  if (activeQuery.isError) {
    return <ErrorState error={activeQuery.error} onRetry={activeQuery.refetch} />
  }

  const approving = dialog?.type === 'approve'
  const isSeat = dialog?.kind === 'seat'

  return (
    <div>
      <PageHeader
        title="Upgrade requests"
        description="Review plan upgrade requests from realtors and seat add-on requests from enterprises."
      />

      <Card className="mb-4">
        <Tabs tabs={TABS} value={tab} onChange={switchTab} className="px-4" />
      </Card>

      {tab === 'seats' && (
        <div className="mb-4 flex flex-wrap items-center gap-3">
          <Select
            value={seatStatus}
            onChange={(event) => {
              setSeatStatus(event.target.value)
              setPage(1)
            }}
            className="w-44"
          >
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="">All statuses</option>
          </Select>
          <span className="ml-auto text-xs text-muted-foreground">
            {formatNumber(seatQuery.data?.meta?.total || 0)} requests
          </span>
        </div>
      )}

      <Card>
        {tab === 'plans' ? (
          <DataTable
            columns={planColumns}
            rows={planQuery.data?.rows || []}
            loading={planQuery.isLoading}
            emptyState={
              <EmptyState
                icon={ArrowTrendingUpIcon}
                title="No upgrade requests"
                description="Requests will appear here when realtors ask for a higher plan."
              />
            }
          />
        ) : (
          <DataTable
            columns={seatColumns}
            rows={seatQuery.data?.rows || []}
            loading={seatQuery.isLoading}
            emptyState={
              <EmptyState
                icon={UserPlusIcon}
                title="No seat requests"
                description="Enterprises can request extra manager or employee seats from their billing page."
              />
            }
          />
        )}
      </Card>

      <Pagination
        page={activeQuery.data?.meta?.current_page || 1}
        lastPage={activeQuery.data?.meta?.last_page || 1}
        total={activeQuery.data?.meta?.total || 0}
        perPage={activeQuery.data?.meta?.per_page || PAGE_SIZE}
        onPageChange={setPage}
      />

      <Modal
        open={Boolean(dialog)}
        onClose={() => setDialog(null)}
        title={
          isSeat
            ? approving
              ? 'Approve seat request'
              : 'Reject seat request'
            : approving
              ? 'Approve upgrade request'
              : 'Reject upgrade request'
        }
        description={
          isSeat
            ? dialog?.request?.enterprise
              ? `${dialog.request.enterprise.name} → +${dialog.request.quantity} ${dialog.request.seat_label}`
              : undefined
            : dialog?.request?.user
              ? `${dialog.request.user.name} → ${requestedPlanOf(dialog.request)?.name || 'requested plan'}`
              : undefined
        }
        footer={
          <>
            <Button variant="outline" onClick={() => setDialog(null)} disabled={processMutation.isPending}>
              Cancel
            </Button>
            <Button
              variant={approving ? 'primary' : 'danger'}
              onClick={submit}
              loading={processMutation.isPending}
            >
              {approving ? 'Approve request' : 'Reject request'}
            </Button>
          </>
        }
      >
        <FormField
          label={approving ? 'Notes (optional)' : 'Rejection reason'}
          htmlFor="upgrade-notes"
          required={!approving}
          error={error}
        >
          <Textarea
            id="upgrade-notes"
            value={notes}
            onChange={(event) => setNotes(event.target.value)}
            invalid={Boolean(error)}
            placeholder={
              approving
                ? 'Anything the requester should know?'
                : 'Explain why this request is being rejected.'
            }
            rows={4}
          />
        </FormField>
      </Modal>
    </div>
  )
}
