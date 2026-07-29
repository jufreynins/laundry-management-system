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

## Phase 3 Additions

### orders (added columns)
- garment_flags (json nullable: tear, missing_button, broken_zipper, color_bleed_risk, delicate_fabric, items_in_pockets)
- customer_declared_item_count (nullable, vs. item_count which is employee-verified)
- customer_acknowledged (boolean)

### order_photos
- id, order_id (FK cascade), disk_path (random UUID filename, never original), mime_type, size_bytes, uploaded_by (nullable)
- Stored on the `local` disk (storage/app/private — not publicly reachable), served only via OrderPhotoController@show after `view` policy check

## Domain Services (Phase 3 additions)

- `App\Services\OrderStatusService::transition()` - enforces `OrderStatusTransitions` map; owner-only override path requires non-empty reason, logs `AuditAction::OVERRIDE_STATUS`
- `App\Services\OrderStatusService::assignStaff()` - staff assignment with audit logging

## Phase 4 Tables

### payments
- id, payment_reference (PAY-YYYY-000001, via number_sequences per year)
- order_id (FK restrict), location_id (FK restrict, denormalized for reporting)
- method (PaymentMethod: cash, external), status (PaymentStatus: completed, voided, refunded, partially_refunded)
- amount (decimal 10,2), reference_note (nullable)
- recorded_by (FK restrict)
- voided_at, voided_by (nullable), void_reason (nullable)
- idempotency_key (nullable unique) - client-supplied, prevents duplicate submission of the same payment
- Indexes: (order_id), (location_id, created_at)

### refunds (immutable, no update/delete routes)
- id, refund_reference (REF-YYYY-000001, via number_sequences per year)
- payment_id (FK restrict), order_id (FK restrict)
- amount (decimal 10,2), reason (required text)
- processed_by (FK restrict), created_at only

## Enums (Phase 4)

### PaymentMethod: cash, external
### PaymentStatus: completed, voided, refunded, partially_refunded

## Domain Services (Phase 4)

- `App\Services\PaymentService::recordPayment()` - validates amount > 0, prevents overpayment (amount <= order.balance_due), rejects duplicate idempotency_key, updates order.amount_paid/balance_due atomically, logs `PAYMENT_RECORDED`
- `App\Services\PaymentService::voidPayment()` - only from `completed` status, requires reason, reverses order balance, logged as `UPDATED` with reason
- `App\Services\PaymentService::refundPayment()` - caps refund at `Payment::refundableAmount()` (amount minus prior refunds), marks payment `refunded`/`partially_refunded`, restores order balance_due, logs `REFUND_ISSUED`
- Never deletes payments/refunds — void and refund are additive status changes, per the "never permanently delete financial records" rule

## Phase 5 Tables

### delivery_zones
- id, location_id (FK cascade), name, description (nullable), fee (decimal 10,2), active

### deliveries
- id, order_id (FK cascade), location_id (FK restrict), delivery_zone_id (nullable FK set null)
- type (DeliveryType: pickup, delivery), status (DeliveryStatus: scheduled, en_route, completed, failed, cancelled)
- scheduled_at, address/city/state/zip (snapshot, not live customer reference)
- driver_id (nullable FK users, set null), fee (snapshotted from zone at scheduling time)
- proof_notes (nullable, required when marking completed), completed_at (nullable)
- created_by
- Indexes: (location_id, status), (driver_id, status), (order_id)

## Enums (Phase 5)

### DeliveryType: pickup, delivery
### DeliveryStatus: scheduled, en_route, completed, failed, cancelled (terminal: completed/failed/cancelled)

## Domain Services (Phase 5)

- `App\Services\DeliveryService::schedule()` - snapshots zone fee at scheduling time; does not auto-transition the parent Order's status (kept decoupled — staff transitions the order separately via the existing status workflow)
- `App\Services\DeliveryService::updateStatus()` - rejects changes once a delivery is in a terminal state; requires non-empty proof-of-delivery notes to mark `completed`
- `App\Services\DeliveryService::assignDriver()` - audit logged

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
