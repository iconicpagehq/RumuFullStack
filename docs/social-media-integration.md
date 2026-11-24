# RUMU CRM — Facebook & Instagram Integration Guide

## 1. Overview & Architecture

The **Social Media & Community** module in RUMU CRM delivers a multi-tenant, unified integration for **Facebook Pages** and **Instagram Professional** (Business and Creator) accounts using ONE single Meta Developer App.

```
                    ┌────────────────────────────────────────────────────────┐
                    │                   Meta Graph API                       │
                    │  (Pages API, Messenger Platform, Instagram Graph API)  │
                    └───────────▲───────────────────────────────┬────────────┘
                                │                               │
                     OAuth / API Requests              Webhooks (X-Hub-Sig)
                                │                               │
┌───────────────────────────────┴───────────────────────────────▼───────────────────────────┐
│                                       RUMU BACKEND (Laravel)                              │
│                                                                                           │
│   MetaApiClient ──► MetaOAuthService ──► MetaTokenService ──► MetaWebhookService          │
│                                                                     │                     │
│   Controllers:                                                  Queued Jobs:              │
│   - SocialAccountController (list, sync, disconnect)            - ProcessMetaWebhook      │
│   - SocialOAuthController (dialog URL, callback, tokens)        - SyncSocialPosts         │
│   - SocialInboxController (conversations, messages, reply)      - SyncSocialComments      │
│   - SocialCommentController (comments, reply, moderate)         - SyncSocialConversations │
│   - SocialPostController (feed posts, media, reactions)         - SyncSocialInsights      │
│   - SocialMentionController (tags, user mentions)               - RefreshMetaToken        │
│   - SocialAnalyticsController (KPIs, engagement, recharts)                                │
│   - SocialWebhookController (challenge verify & HMAC-256)                                 │
│                                                                                           │
│   Storage (Encrypted Tokens + Strict Tenancy):                                            │
│   - social_accounts, social_pages, social_posts, social_comments,                         │
│   - social_conversations, social_messages, social_mentions, social_insights               │
└───────────────────────────────────────────────▲───────────────────────────────────────────┘
                                                │
                                    REST API (Sanctum Auth)
                                                │
┌───────────────────────────────────────────────┴───────────────────────────────────────────┐
│                                       RUMU FRONTEND (React)                               │
│                                                                                           │
│   src/modules/social/                                                                     │
│   ├── pages/                                                                              │
│   │   ├── SocialDashboard.jsx (Overview, KPIs, Quick Links, Recharts)                     │
│   │   ├── SocialAccounts.jsx (Connect Meta, Disconnect, Sync status)                      │
│   │   ├── SocialInbox.jsx (Unified Inbox: Messenger & Instagram DMs)                      │
│   │   ├── SocialComments.jsx (Moderate, Reply, Hide, Delete comments)                     │
│   │   ├── SocialPosts.jsx (Media grid, engagement metrics, original links)                │
│   │   ├── SocialMentions.jsx (Instagram & Facebook tagged mentions)                       │
│   │   └── SocialAnalytics.jsx (Reach, impressions, audience reactions)                    │
│   ├── components/                                                                         │
│   │   ├── AccountCard.jsx, AccountConnection.jsx                                          │
│   │   ├── ConversationList.jsx, MessageThread.jsx                                         │
│   │   ├── CommentCard.jsx, PostCard.jsx, MentionCard.jsx, AnalyticsCharts.jsx             │
│   └── services/                                                                           │
│       └── socialService.js (Centralized Axios endpoints)                                  │
└───────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Meta Developer App Configuration

### Selected Use Cases in Meta Developer Portal
1. **Manage messaging & content on Instagram**
2. **Manage everything on your Page (Facebook Pages API)**
3. **Engage with customers on Messenger from Meta**

### Required Permissions (Scopes)
- `pages_show_list`
- `pages_read_engagement`
- `pages_manage_posts`
- `pages_manage_engagement`
- `pages_messaging`
- `instagram_basic`
- `instagram_manage_comments`
- `instagram_manage_messages`
- `instagram_manage_insights`
- `read_insights`
- `business_management`

### Environment Variables
Configure these variables in your `Backend/.env`:

```env
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
META_REDIRECT_URI=https://your-crm-domain.com/realtor/social/accounts
META_GRAPH_VERSION=v20.0
META_WEBHOOK_VERIFY_TOKEN=your_secure_random_verify_token
```

---

## 3. Webhook Setup in Meta Dashboard

1. Navigate to **Meta Developer Portal** > **App Dashboard** > **Webhooks**.
2. Select **Page** or **Instagram** product.
3. Configure Callback URL:
   - **Callback URL:** `https://your-crm-domain.com/api/v1/social/meta/webhook`
   - **Verify Token:** The exact value of `META_WEBHOOK_VERIFY_TOKEN`.
4. Subscribe to the following fields:
   - **Page subscriptions:** `feed`, `mention`, `messages`, `messaging_postbacks`, `message_reads`, `message_deliveries`.
   - **Instagram subscriptions:** `messages`, `messaging_postbacks`, `comments`, `mentions`.
5. Every incoming request is cryptographically validated using HMAC SHA-256 against `X-Hub-Signature-256` and processed asynchronously on Laravel queues.

---

## 4. Multi-Tenant Security & Storage

- All 11 tables store `company_id` (foreign key to `enterprises.id`) and `user_id` (foreign key to `users.id`).
- When accessed under Enterprise workspace (`EnterpriseMiddleware`), all queries scope strictly to `company_id`.
- When accessed under individual Realtor workspace, queries scope to `user_id` where `company_id` is null or matches active enterprise.
- Access tokens for both Facebook Users and Pages are encrypted at rest using Laravel's `'access_token' => 'encrypted'` model casting.
- OAuth states (`social_oauth_states`) are cryptographically random, single-use, bound to the authenticated user and company, and expire after 15 minutes.

---

## 5. Endpoints Reference

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/social/connect/meta` | Generate single-use OAuth authorization URL |
| `GET` | `/api/v1/social/connect/meta/callback` | Exchange OAuth code, verify permissions, sync pages |
| `GET` | `/api/v1/social/accounts` | List connected social accounts and pages |
| `DELETE` | `/api/v1/social/accounts/{id}` | Disconnect social account and deactivate pages |
| `POST` | `/api/v1/social/accounts/{id}/sync` | Dispatch manual synchronization job |
| `GET` | `/api/v1/social/posts` | Paginated posts (Facebook and Instagram) |
| `GET` | `/api/v1/social/posts/{id}` | Detailed post view with comment thread |
| `GET` | `/api/v1/social/posts/{id}/comments` | List comments and replies |
| `POST` | `/api/v1/social/comments/{id}/reply` | Reply to a comment via Graph API |
| `PATCH` | `/api/v1/social/comments/{id}` | Moderate comment (hide/unhide) |
| `DELETE` | `/api/v1/social/comments/{id}` | Delete comment |
| `GET` | `/api/v1/social/inbox/conversations` | List Messenger and Instagram conversations |
| `GET` | `/api/v1/social/inbox/conversations/{id}/messages` | Message thread history |
| `POST` | `/api/v1/social/inbox/conversations/{id}/messages` | Send direct message / Messenger reply |
| `PATCH` | `/api/v1/social/inbox/conversations/{id}` | Assign conversation / update internal notes |
| `GET` | `/api/v1/social/mentions` | List tagged mentions across platforms |
| `GET` | `/api/v1/social/analytics/overview` | Overall social KPIs and Recharts time-series data |
| `GET` | `/api/v1/social/analytics/posts/{id}` | Post-level media analytics |
| `GET` | `/api/v1/social/meta/webhook` | Meta Webhook challenge verification (Public) |
| `POST` | `/api/v1/social/meta/webhook` | Meta Webhook receiver with HMAC-256 validation (Public) |

