# Meta Integration Guide

This document explains the architecture and operational details of the Meta Lead Ads and CAPI integration.

## Architecture

The integration consists of:
1. **OAuth Flow**: Managed via `AuthController`. Uses `socialite` to authenticate the user and `MetaLeadApiService@syncUserPagesFromToken` to pull Facebook Pages the user manages. Pages are stored in `meta_pages`.
2. **MetaLeadApiService**: Handles all HTTP requests to the Meta Graph API. Responsible for listing forms and fetching lead details. Throws `MetaApiException` on errors.
3. **MetaLeadImportService**: Business logic for normalizing leads and importing them into the CRM. Maps incoming webhook requests to `Campaign` using `meta_campaign_forms`.
4. **Queue Processing**: Webhooks are pushed to a Redis queue (`meta-leads`) and processed asynchronously by `ProcessWebhookLeadJob`.

## Error Handling & Reliability

- **MetaApiException**: Custom exception that distinguishes between transient (network error, rate limit) and permanent (OAuth invalidation, bad permissions) errors.
- **Retries**: `ProcessWebhookLeadJob` attempts up to 3 times for transient errors with exponential backoff.
- **Monitoring**: `meta_pages` table tracks `consecutive_failures`, `last_successful_api_request_at`, and `last_failed_api_request_at`.
- **Logs**: Detailed integration events are stored in `meta_sync_logs` for user visibility in the Meta Integration Dashboard.

## Caching
- **Lead Forms**: Fetched lead forms are cached for 10 minutes (`meta_lead_forms_{page_id}`). Users can force-refresh via the UI.

## Database Schema
- `meta_pages`: Stores Page IDs, names, encrypted access tokens, and API health metrics.
- `meta_campaign_forms`: Junction table mapping a `campaign_id` to a `page_id` and `form_id`.
- `meta_sync_logs`: Auditing table for sync events.

## Security
- `access_token` in `meta_pages` is cast to `encrypted`.
- Avoid logging Personally Identifiable Information (PII) from webhook payloads.
