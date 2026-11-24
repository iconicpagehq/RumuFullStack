<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta Conversions API for CRM (Reverse Signal)
    |--------------------------------------------------------------------------
    |
    | Sends lead funnel events back to Meta using meta_lead_id as lead_id.
    | Requires a CRM Dataset in Events Manager and a System User access token.
    |
    */

    'enabled' => env('META_CAPI_ENABLED', false),

    'dataset_id' => env('META_CAPI_DATASET_ID'),

    'access_token' => env('META_CAPI_ACCESS_TOKEN'),

    'graph_version' => env('META_CAPI_GRAPH_VERSION', 'v19.0'),

    'lead_event_source' => env('META_CAPI_LEAD_EVENT_SOURCE', env('APP_NAME', 'backend-crm-app')),

    'event_source' => 'crm',

    /*
    | Send initial "Lead" event when a Meta lead is imported (not on manual create).
    */
    'send_on_import' => env('META_CAPI_SEND_ON_IMPORT', true),

    /*
    | Map CRM lead status to Meta Conversion Leads event names.
    | Set to null to skip sending for that status (e.g. "new" on manual updates).
    */
    'status_events' => [
        'new' => 'Lead',
        'contacted' => 'LeadContacted',
        'qualified' => 'LeadQualified',
        'proposal' => 'LeadProposal',
        'negotiation' => 'LeadNegotiation',
        'won' => 'LeadConverted',
        'lost' => 'LeadRejected',
    ],

    /*
    | Statuses that trigger CAPI on observer update. "new" is excluded by default
    | so manual creates do not fire; import hook handles initial Lead event.
    */
    'observer_statuses' => [
        'contacted',
        'qualified',
        'proposal',
        'negotiation',
        'won',
        'lost',
    ],

    'meta_sources' => ['meta_ads'],

    'meta_lead_sources' => ['facebook'],

];
