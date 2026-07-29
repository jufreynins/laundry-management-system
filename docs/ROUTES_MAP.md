# Routes Map

## Phase 0 Routes

### Public Routes (No Authentication Required)

#### Auth
- `GET /login` - Login form
- `POST /login` - Process login (DenyUnlessBrowser, ThrottleRequests)
- `POST /logout` - Process logout (requires auth)
- `GET /forgot-password` - Forgot password form
- `POST /forgot-password` - Send reset link (ThrottleRequests)
- `GET /reset-password/{token}` - Reset password form
- `POST /reset-password` - Process password reset (ThrottleRequests)

### Protected Routes (Require Authentication)

#### Authenticated Users
- `GET /dashboard` - Main dashboard
- `GET /profile` - Edit profile
- `PATCH /profile` - Update profile
- `POST /logout` - Logout

#### Owner Only
- `GET /admin/business-settings` - Edit business settings
- `PATCH /admin/business-settings` - Update business settings
- `GET /admin/locations` - List locations
- `GET /admin/locations/create` - Create location form
- `POST /admin/locations` - Store location
- `GET /admin/locations/{id}/edit` - Edit location form
- `PATCH /admin/locations/{id}` - Update location
- `DELETE /admin/locations/{id}` - Delete location

#### Manager (Owner + Manager)
- `GET /admin/users` - List users
- `GET /admin/users/create` - Create user form
- `POST /admin/users` - Store user
- `GET /admin/users/{id}/edit` - Edit user form
- `PATCH /admin/users/{id}` - Update user
- `DELETE /admin/users/{id}` - Delete user

#### All Authenticated
- `GET /audit-logs` - View recent audit logs (own location only for non-managers)

## Phase 1 Routes (Implemented)

- `resource customers` (except destroy) - CustomerController - location-scoped, accountant read-only
- `resource services` (except destroy) - ServiceController - view: any auth user; create/update: admin only
- `resource admin/users` (except destroy) - Admin\UserController - owner/manager only
- `resource admin/locations` (except destroy) - Admin\LocationController - owner/manager view; owner-scoped create/update
- `GET/PATCH admin/settings` - Admin\BusinessSettingsController - admin only

## Phase 2 Routes (Implemented)

- `GET orders` - OrderController@index - location-scoped, all authenticated
- `GET orders/create` - OrderController@create - not accountant
- `POST orders` - OrderController@store - not accountant, server-side pricing via OrderService
- `GET orders/{order}` - OrderController@show - location-scoped

## Phase 3 Routes (Implemented)

- `PATCH orders/{order}/status` - OrderController@updateStatus - not accountant; override requires Owner role + reason + confirm_override checkbox
- `PATCH orders/{order}/assign` - OrderController@assign - not accountant
- `POST orders/{order}/photos` - OrderPhotoController@store - not accountant; validated image upload, random filename
- `GET orders/{order}/photos/{photo}` - OrderPhotoController@show - location-scoped, streams from private disk

## Phase 4 Routes (Implemented)

- `GET orders/{order}/receipt` - ReceiptController@show - location-scoped, printable
- `GET orders/{order}/claim-ticket` - ReceiptController@claimTicket - location-scoped, printable
- `GET payments` - PaymentController@index - location-scoped
- `POST orders/{order}/payments` - PaymentController@store - not accountant; requires idempotency_key
- `PATCH payments/{payment}/void` - PaymentController@void - Owner/Manager only, requires reason
- `POST payments/{payment}/refund` - PaymentController@refund - Owner/Manager only, requires reason
