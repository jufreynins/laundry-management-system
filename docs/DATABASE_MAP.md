# Database Map

## Core Tables (Phase 0)

### users
- id
- name
- email (unique)
- password (hashed)
- role (enum: owner, manager, cashier, staff, driver, accountant)
- location_id (nullable, null = all locations)
- active (boolean)
- last_login_at (timestamp)
- email_verified_at (timestamp, nullable)
- remember_token
- created_at, updated_at

### locations
- id
- business_id (foreign)
- name
- address
- city
- state
- zip
- phone
- timezone (e.g., America/New_York)
- active (boolean)
- created_at, updated_at

### business_settings
- id
- location_id (foreign)
- key (string)
- value (text)
- encrypted (boolean)
- created_at, updated_at

Keys:
- `tax_rate` - decimal
- `sales_tax_enabled` - boolean
- `store_name` - string
- `state` - US state code
- `business_hours_open` - time
- `business_hours_close` - time
- `minimum_order_amount` - decimal

### audit_logs
- id
- user_id (nullable)
- action (enum: created, updated, deleted, login_failed, login, password_changed, mfa_changed)
- model (string, e.g., "Order")
- model_id (integer)
- location_id (nullable)
- old_values (json, nullable)
- new_values (json, nullable)
- reason (string, nullable)
- ip_address (string)
- user_agent (string)
- created_at

## Enums (Phase 0)

### UserRole
- OWNER
- MANAGER
- CASHIER
- LAUNDRY_STAFF
- DRIVER
- ACCOUNTANT

### AuditAction
- CREATED
- UPDATED
- DELETED
- LOGIN
- LOGIN_FAILED
- PASSWORD_CHANGED
- MFA_CHANGED
- ROLE_CHANGED
- PERMISSION_CHANGED
- OVERRIDE_STATUS
- PAYMENT_RECORDED
- REFUND_ISSUED
- DISCOUNT_APPLIED

### OrderStatus
- DRAFT
- CHECKED_IN
- TAGGED
- SORTING
- WASHING
- DRYING
- FINISHING
- QUALITY_CHECK
- READY_FOR_PICKUP
- OUT_FOR_DELIVERY
- COMPLETED
- CANCELLED
- ON_HOLD

## Relationships

**User**
- hasMany: AuditLog
- belongsTo: Location (nullable)

**Location**
- belongsToMany: User (pivot table users_locations)
- hasMany: BusinessSettings
- hasMany: AuditLog

**AuditLog**
- belongsTo: User (nullable)
- belongsTo: Location (nullable)
