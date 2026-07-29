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
