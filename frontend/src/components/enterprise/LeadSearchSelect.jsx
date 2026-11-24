import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { enterpriseApi } from '@/api/enterprise'
import { useDebounce } from '@/hooks/useDebounce'
import { Input, Select } from '@/components/ui/Input'
import { stageLabel } from '@/utils/enterprise'

export const LeadSearchSelect = ({
  id,
  value,
  onChange,
  defaultLead = null,
  invalid = false,
  disabled = false,
  hint = 'Search by name, phone or email.',
}) => {
  const [search, setSearch] = useState('')
  const debounced = useDebounce(search, 300)

  useEffect(() => {
    setSearch('')
  }, [defaultLead?.id])

  const { data, isFetching } = useQuery({
    queryKey: ['enterprise', 'lead-picker', { search: debounced }],
    queryFn: async () => {
      const response = await enterpriseApi.leads.list({
        search: debounced || undefined,
        per_page: 20,
      })
      return response.data?.data || []
    },
    staleTime: 20000,
  })

  if (defaultLead) {
    return (
      <Select id={id} value={defaultLead.id} disabled>
        <option value={defaultLead.id}>
          {defaultLead.name} · {stageLabel(defaultLead.stage)}
        </option>
      </Select>
    )
  }

  const leads = data || []
  const hasSelected = value && !leads.some((lead) => String(lead.id) === String(value))

  return (
    <div className="space-y-2">
      <Input
        value={search}
        onChange={(event) => setSearch(event.target.value)}
        placeholder="Search leads…"
        aria-label="Search leads"
        disabled={disabled}
      />
      <Select
        id={id}
        value={value || ''}
        onChange={onChange}
        invalid={invalid}
        disabled={disabled}
      >
        <option value="">{isFetching ? 'Searching…' : 'No lead'}</option>
        {hasSelected && <option value={value}>Selected lead #{value}</option>}
        {leads.map((lead) => (
          <option key={lead.id} value={lead.id}>
            {lead.name} · {stageLabel(lead.stage)}
            {lead.phone ? ` · ${lead.phone}` : ''}
          </option>
        ))}
      </Select>
      {hint && <p className="form-hint">{hint}</p>}
    </div>
  )
}
