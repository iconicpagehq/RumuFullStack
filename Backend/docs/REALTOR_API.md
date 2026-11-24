# Rumu Realtor Mobile API

REST API for the Kotlin/Android realtor app. All endpoints return JSON.

## Base URL

| Environment | Base URL |
|-------------|----------|
| Local (device/emulator on same machine) | `http://127.0.0.1:8000/api/v1` |
| Android emulator → host machine | `http://10.0.2.2:8000/api/v1` |
| Production | `https://your-domain.com/api/v1` |

Start the server: `php artisan serve`

## Authentication

Uses **Laravel Sanctum** bearer tokens.

### Headers (authenticated requests)

```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Register

`POST /auth/register`

```json
{
  "name": "Jane Realtor",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response `201`**

```json
{
  "success": true,
  "data": {
    "token": "1|abc...",
    "token_type": "Bearer",
    "user": { "id": 1, "name": "Jane Realtor", "email": "jane@example.com", "role": "realtor", "plan": { ... } }
  },
  "message": "Registration successful."
}
```

### Login

`POST /auth/login`

```json
{
  "email": "jane@example.com",
  "password": "secret123"
}
```

**Response `200`** — same token shape as register.

**Errors:** `403` if user is not a realtor (e.g. admin account).

### Current user

`GET /auth/me` — requires token.

### Logout

`POST /auth/logout` — revokes current token.

`POST /auth/logout-all` — revokes all tokens for the user.

---

## Response format

### Success

```json
{
  "success": true,
  "data": { },
  "message": "Optional message"
}
```

### Paginated

```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  },
  "message": null
}
```

### Error

```json
{
  "success": false,
  "message": "Human-readable error",
  "errors": { "field": ["Validation message"] }
}
```

### HTTP status codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 401 | Missing or invalid token |
| 403 | Forbidden (wrong role, plan limit, suspended subscription on write) |
| 404 | Not found |
| 422 | Validation failed |
| 409 | Conflict (e.g. duplicate upgrade request) |

---

## Subscription suspended

When a realtor's subscription is suspended:

- **GET** requests still work (read-only).
- **POST / PUT / PATCH / DELETE** return `403` with message about suspended subscription.

---

## Enums

### Lead status

`new`, `contacted`, `qualified`, `proposal`, `negotiation`, `won`, `lost`

### Campaign status

`active`, `paused`, `completed`

### Inventory unit status

`available`, `hold`, `booked`, `sold`

### Unit layout

`studio`, `1_bhk`, `2_bhk`, `3_bhk`, `4_bhk`, `4+_bhk`

### Customer definition

A lead appears under **Customers** when:

- `status` is `won`, **or**
- linked unit has status `booked` or `sold`

---

## Realtor endpoints

All require `Authorization: Bearer {token}` and realtor role.

### Dashboard

`GET /realtor/dashboard`

Returns campaign/lead status counts, 6-month charts, recent campaigns, plan expiry warning.

---

### Campaigns

| Method | Path | Description |
|--------|------|-------------|
| GET | `/realtor/campaigns` | List campaigns |
| POST | `/realtor/campaigns` | Create campaign |
| GET | `/realtor/campaigns/{id}` | Campaign detail + leads |
| PUT | `/realtor/campaigns/{id}` | Update campaign |
| DELETE | `/realtor/campaigns/{id}` | Delete campaign |

**Create body**

```json
{
  "name": "Summer Launch",
  "description": "Optional",
  "status": "active"
}
```

---

### Leads

| Method | Path | Description |
|--------|------|-------------|
| GET | `/realtor/campaigns/{campaign_id}/leads` | Paginated leads |
| POST | `/realtor/campaigns/{campaign_id}/leads` | Create lead |
| GET | `/realtor/leads/{id}` | Lead detail + notes + unit |
| PUT | `/realtor/leads/{id}` | Update lead |
| PATCH | `/realtor/leads/{id}/status` | Update status only |
| POST | `/realtor/leads/{id}/notes` | Add note |

**Create / update body (key fields)**

```json
{
  "name": "Rahul Sharma",
  "email": "rahul@example.com",
  "phone": "+919876543210",
  "company": null,
  "status": "new",
  "note": "Called once",
  "inventory_unit_id": 5,
  "custom_fields": {
    "client_type": "buyer",
    "budget_range": "1cr_1.5cr",
    "lead_priority": "hot"
  }
}
```

**Status update**

```json
{ "status": "won" }
```

**Add note**

```json
{ "note": "Site visit scheduled for Saturday." }
```

---

### Customers

`GET /realtor/customers`

Query params:

| Param | Description |
|-------|-------------|
| `search` | Name, email, or phone |
| `status` | `won`, `booked`, or `sold` |
| `page` | Page number |

**Response `data`**

```json
{
  "stats": { "total": 10, "won": 4, "booked": 3, "sold": 5 },
  "customers": [ ]
}
```

---

### Inventory (read-only v1)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/realtor/inventory/projects` | List projects + availability summary |
| GET | `/realtor/inventory/projects/{id}` | Project with towers/floors |
| GET | `/realtor/inventory/floors/{floor_id}/units` | Units on a floor |
| GET | `/realtor/inventory/units/search` | Search assignable units |

**Floor units query:** `?status=available&layout=2_bhk`

**Unit search query:** `?q=101&project_id=2`

---

### Plans

`GET /realtor/plans` — active plans, current plan, pending upgrade, expiry info.

`POST /realtor/plans/upgrade`

```json
{ "plan_id": 2 }
```

---

### Profile & settings

| Method | Path | Description |
|--------|------|-------------|
| GET | `/realtor/profile` | Profile + stats |
| PUT | `/realtor/profile` | Update name, email, phone, company, bio |
| PUT | `/realtor/profile/password` | Change password |
| PUT | `/realtor/settings/notifications` | Email/marketing toggles |

**Password body**

```json
{
  "current_password": "oldpass",
  "password": "newpass123",
  "password_confirmation": "newpass123"
}
```

**Notifications body**

```json
{
  "email_notifications": true,
  "marketing_emails": false
}
```

---

## Kotlin / Retrofit example

### Gradle dependencies

```kotlin
implementation("com.squareup.retrofit2:retrofit:2.11.0")
implementation("com.squareup.retrofit2:converter-gson:2.11.0")
implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
```

### API models

```kotlin
data class ApiResponse<T>(
    val success: Boolean,
    val data: T?,
    val message: String?,
    val errors: Map<String, List<String>>? = null
)

data class LoginRequest(val email: String, val password: String)

data class AuthData(
    val token: String,
    val token_type: String,
    val user: UserDto
)
```

### Retrofit interface

```kotlin
interface RumuApi {
    @POST("auth/login")
    suspend fun login(@Body body: LoginRequest): ApiResponse<AuthData>

    @GET("auth/me")
    suspend fun me(): ApiResponse<UserDto>

    @GET("realtor/dashboard")
    suspend fun dashboard(): ApiResponse<DashboardDto>

    @GET("realtor/campaigns")
    suspend fun campaigns(): ApiResponse<List<CampaignDto>>

    @GET("realtor/customers")
    suspend fun customers(
        @Query("search") search: String? = null,
        @Query("status") status: String? = null,
        @Query("page") page: Int? = null
    ): CustomersResponse
}
```

### Auth interceptor

```kotlin
class AuthInterceptor(private val tokenProvider: () -> String?) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request().newBuilder()
            .addHeader("Accept", "application/json")
            .apply {
                tokenProvider()?.let { addHeader("Authorization", "Bearer $it") }
            }
            .build()
        return chain.proceed(request)
    }
}

val client = OkHttpClient.Builder()
    .addInterceptor(AuthInterceptor { tokenStore.get() })
    .addInterceptor(HttpLoggingInterceptor().apply { level = Body })
    .build()

val api = Retrofit.Builder()
    .baseUrl("http://10.0.2.2:8000/api/v1/")
    .client(client)
    .addConverterFactory(GsonConverterFactory.create())
    .build()
    .create(RumuApi::class.java)
```

### Login flow

```kotlin
val response = api.login(LoginRequest(email, password))
if (response.success && response.data != null) {
    tokenStore.save(response.data.token)
    // Navigate to dashboard
} else {
    // Show response.message
}
```

---

## cURL examples

```bash
# Login
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"realtor@example.com","password":"password"}'

# Dashboard
curl http://127.0.0.1:8000/api/v1/realtor/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Create lead
curl -X POST http://127.0.0.1:8000/api/v1/realtor/campaigns/1/leads \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Lead","status":"new","phone":"9876543210"}'
```

---

## Not in v1 (web only / future)

- Inventory structure CRUD (towers, wings, floors, bulk units)
- CSV lead import
- Meta lead sync / OAuth (Google, Facebook)
- Avatar multipart upload
- Account deletion

Use the web app at `/realtor/*` for these features until added to a future API version.
