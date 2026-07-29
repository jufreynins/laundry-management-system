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

## Phase 1 Tables

### customers
- id, customer_number (CUS-000001, auto via id, not fillable)
- location_id (FK, restrict delete)
- name, email (nullable), phone
- address, city, state, zip (nullable)
- operational_consent, marketing_consent (booleans)
- notes, active
- Indexes: (location_id, name), (location_id, phone)

### services
- id, name, category (enum string), pricing_type (enum string)
- base_price, minimum_charge (decimal 10,2)
- taxable, rush_eligible, estimated_duration_minutes, active

### service_prices (location price overrides)
- id, service_id (FK), location_id (FK), price (decimal 10,2), active
- unique(service_id, location_id)

## Enums (Phase 1)

### PricingType: per_pound, per_item, flat_fee, hourly, custom_quote
### ServiceCategory: wash_fold, wash_press, dry_cleaning, alterations, shoe_cleaning, delivery, rush, add_on

## Phase 2 Tables

### orders
- id, order_number (LND-YYYY-000001, via number_sequences per year)
- customer_id (FK restrict), location_id (FK restrict)
- intake_channel (enum), status (OrderStatus enum), intake_at, promised_at
- rush, assigned_user_id (nullable), created_by
- weight_lbs, item_count, bag_count (nullable)
- stain_notes, customer_instructions, internal_notes (nullable text)
- subtotal, discount_amount, tax_amount, tip_amount, total, amount_paid, balance_due (decimal 10,2)
- Indexes: (location_id, status), (customer_id), (location_id, promised_at)

### order_items
- id, order_id (FK cascade), service_id (FK restrict)
- description, pricing_type, quantity, unit_price, line_total (snapshotted from Service at order time)
- taxable (snapshotted)

### order_status_histories (immutable, no update/delete routes)
- id, order_id, from_status (nullable), to_status, changed_by (nullable), reason (nullable), is_override, created_at only

### number_sequences (generic atomic sequence generator)
- id, key (unique, e.g. "order-2026"), next_value
- Used via SequenceGenerator::next() with row locking inside a DB transaction

## Enums (Phase 2)

### IntakeChannel: walk_in, pickup, delivery, commercial

## Domain Services

- `App\Services\OrderService::createOrder()` - the only path that creates orders; wraps everything in one DB transaction, computes all prices/tax/totals server-side from `Service` records (never trusts client-submitted prices)
- `App\Services\SequenceGenerator` - atomic, DB-safe number generation (order numbers, will be reused for payment numbers in Phase 4)
- `App\Services\OrderStatusTransitions` - centralized status transition map (Phase 3 will enforce this)
- `App\Services\AuditLogService` - shared audit log writer used by all controllers/services

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
