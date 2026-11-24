# Meta Conversions API for CRM (Reverse Signal)

This CRM sends lead funnel events back to Meta when you update lead statuses. Meta uses these signals to optimize lead ads for **quality** (Conversion Leads), not just form volume.

## How it works

1. Leads arrive from Meta Lead Ads (API sync or CSV) with a **`meta_lead_id`** stored on each lead.
2. When you change status in the CRM (`contacted`, `qualified`, `won`, etc.), the app queues a server-side event to Meta.
3. Meta matches events using `lead_id` (= `meta_lead_id`) plus optional hashed email/phone.

## Prerequisites (Meta Business)

1. **Events Manager** → Create or select a **CRM Dataset** (Conversion Leads workflow).
2. **Business Settings** → **System Users** → Generate a token with access to:
   - The CRM Dataset
   - `ads_management` (as required by your Business Manager setup)
3. Copy the **Dataset ID** and **access token** into your server `.env`.

## Environment variables

```env
META_CAPI_ENABLED=true
META_CAPI_DATASET_ID=your_crm_dataset_id
META_CAPI_ACCESS_TOKEN=your_system_user_token
META_CAPI_GRAPH_VERSION=v19.0
META_CAPI_LEAD_EVENT_SOURCE="${APP_NAME}"
META_CAPI_SEND_ON_IMPORT=true
```

Keep `META_CAPI_ENABLED=false` until dataset and token are set.

## CRM status → Meta events

| CRM status   | Meta event        |
|-------------|-------------------|
| new         | Lead (on import only) |
| contacted   | LeadContacted     |
| qualified   | LeadQualified     |
| proposal    | LeadProposal      |
| negotiation | LeadNegotiation   |
| won         | LeadConverted     |
| lost        | LeadRejected      |

Mapping is defined in `config/meta_capi.php`.

## Verification

1. Import or sync a test lead from Meta (must have `meta_lead_id`).
2. Update status to `qualified`, then `won`.
3. Open **Events Manager** → your CRM Dataset → **Test events** / **Overview**.
4. Confirm events include custom parameters:
   - `lead_event_source`
   - `event_source` = `crm`
5. Check **Diagnostics** for matched `lead_id` and coverage.

### Coverage target

Meta recommends sending events for **at least 60%** of leads generated on Facebook. Update every Meta lead’s status in the CRM when possible.

### Funnel depth

Send **at least 2–3 stages** (e.g. contacted → qualified → won). Sending only `won` is not enough for optimization.

## Queue worker

Events are dispatched via `SendMetaCapiLeadEventJob`. With `QUEUE_CONNECTION=database`, run:

```bash
php artisan queue:work
```

In local/testing, `QUEUE_CONNECTION=sync` runs jobs immediately.

## Troubleshooting

| Issue | Check |
|-------|--------|
| No events in Meta | `META_CAPI_ENABLED`, dataset ID, token; lead has `meta_lead_id` and `source=meta_ads` |
| Events rejected | Valid `lead_id` from Meta; email/phone hashed correctly |
| Duplicates skipped | By design — one successful send per `(lead, status)`; use **Resend** on lead detail if needed |
| Failed rows | `meta_capi_event_logs` table or lead detail **Meta sync** section |

## References

- [CRM integration — implementation](https://developers.facebook.com/docs/marketing-api/conversions-api/conversion-leads-integration/crm-integration/3-implementing-the-crm-integration/)
- [Conversions API for CRM (platforms)](https://developers.facebook.com/docs/marketing-api/conversions-api/guides/conversions-api-crm-for-platforms)
