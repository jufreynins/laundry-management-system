# Architectural Decisions

## Phase 0 Decisions

### Decision 1: Role-Based Authorization with Enums
**Decision**: Use PHP enums for UserRole instead of a database-backed roles table
**Reason**: Roles are fixed business roles (Owner, Manager, Cashier, Staff, Driver, Accountant). Using enums provides type safety, makes roles obvious in code, and avoids unnecessary database queries.
**Date**: 2026-07-29
**Affected Module**: Authentication, Authorization, User model
**Trade-off**: Role changes require code deployment, not database configuration. Acceptable because roles rarely change.

### Decision 2: Location-Based Access Control
**Decision**: All operational records include location_id. Users may be restricted to a single location.
**Reason**: Multi-location support is built into schema from the start, avoiding later migration complexity. Single-location businesses set all users to the same location.
**Date**: 2026-07-29
**Affected Module**: All CRUD operations, query scopes
**Trade-off**: Every query needs location filtering. Scope-based protection ensures correctness.

### Decision 3: Audit Logs for Financial & Permission Changes
**Decision**: Store immutable audit logs for all financial operations, role changes, and privileged overrides.
**Reason**: Required for regulatory compliance, dispute resolution, and fraud detection. Events are logged via model observers.
**Date**: 2026-07-29
**Affected Module**: AuditLog model, model observers
**Trade-off**: Storage and query overhead. Indexed on action, model, created_at.

### Decision 4: Decimal for All Money
**Decision**: Use `decimal(10, 2)` for all money fields, never floats.
**Reason**: Floating-point arithmetic introduces rounding errors. Decimal arithmetic is correct for financial calculations.
**Date**: 2026-07-29
**Affected Module**: All price, total, discount fields
**Trade-off**: Requires explicit decimal conversions in some contexts. Non-negotiable for financial correctness.

### Decision 5: Business Settings Model for Configuration
**Decision**: Use BusinessSettings table instead of .env or config files for location-specific business rules.
**Reason**: Settings must be editable by the owner via the UI without redeployment. Tax rates, store hours, minimum order amounts vary by location.
**Date**: 2026-07-29
**Affected Module**: Business settings CRUD, configuration access
**Trade-off**: Settings loaded from database; cache them to minimize queries.

### Decision 6: Gentelella for Admin Interface
**Decision**: Customize Gentelella (open-source admin template) for the dashboard instead of building from scratch.
**Reason**: Gentelella provides a professional, responsive admin layout that requires minimal customization. Saves token usage.
**Date**: 2026-07-29
**Affected Module**: Layout, dashboard, all admin views
**Trade-off**: Gentelella is not perfectly minimal but is already installed and widely used. Branding is generic until customized.

### Decision 7: Laravel Policies for Authorization
**Decision**: Use Laravel's policy-based authorization instead of gate checks.
**Reason**: Policies are more maintainable, can be authorized via middleware, and tie authorization to models.
**Date**: 2026-07-29
**Affected Module**: All protected resources (users, locations, settings)
**Trade-off**: Policies require explicit creation for each model. Necessary for consistent authorization.

### Decision 8: Database Transactions Required for Orders
**Decision**: All order creation, modification, and payment must use database transactions.
**Reason**: Orders are atomic operations. A failed item insertion cannot leave an unpaid order in the system.
**Date**: 2026-07-29
**Affected Module**: Order creation, payment processing
**Trade-off**: Transactions add latency. Non-negotiable for financial integrity.

### Decision 9: SQLite for Testing, MySQL for Production
**Decision**: Use SQLite for unit and feature tests; MySQL for production and staging.
**Reason**: SQLite is fast, requires no setup, and is built into PHP. For testing, it's sufficient and isolated.
**Date**: 2026-07-29
**Affected Module**: phpunit.xml, testing configuration
**Trade-off**: SQLite syntax differences (rare) must be tested. Test database configuration must match.

### Decision 10: Never Delete Financial Records
**Decision**: Financial records (payments, refunds, orders) use soft deletes or status updates, never hard deletes.
**Reason**: Deleting financial records breaks audit trails and breaks tax reconciliation.
**Date**: 2026-07-29
**Affected Module**: Payment, refund, order models
**Trade-off**: Requires migration to soft_deletes for historical accuracy. Necessary for compliance.

## Phase 0 Backfill + Phase 1 Decisions

### Decision 11: Bootstrap 5 via npm instead of Tailwind
**Decision**: Replaced the default Laravel Tailwind scaffold with Bootstrap 5 (npm package, precompiled CSS import) and a hand-rolled Gentelella-style sidebar layout.
**Reason**: Master spec requires Bootstrap 5 + customized Gentelella. Importing `bootstrap/dist/css/bootstrap.min.css` avoids needing the `sass` compiler toolchain for the SCSS source, keeping the build simple.
**Date**: 2026-07-29
**Affected Module**: resources/css/app.css, resources/js/app.js, vite.config.js, layouts.app
**Trade-off**: Using precompiled CSS means Bootstrap's Sass variables can't be customized at build time; theme colors are overridden via CSS custom properties in app.css instead.

### Decision 12: CSP style-src 'unsafe-inline'
**Decision**: SecurityHeaders middleware allows `'unsafe-inline'` for `style-src` only (not `script-src`).
**Reason**: Bootstrap's default components and a few inline `style="max-width:..."` attributes in Blade views rely on inline styles. Locking down `script-src` to `'self'` (no unsafe-inline/eval) is the higher-value protection against XSS.
**Date**: 2026-07-29
**Affected Module**: app/Http/Middleware/SecurityHeaders.php
**Trade-off**: Slightly weaker CSP than a strict nonce-based policy. Acceptable for MVP; revisit with nonces if a stricter policy is required later.

### Decision 13: Customer/Service auto-generated identifiers via forceFill
**Decision**: `customer_number` is deliberately excluded from `$fillable` and set via `forceFill()->saveQuietly()` in a model `created` event, keyed off the auto-increment `id`.
**Reason**: Prevents mass-assignment tampering (a user could otherwise submit `customer_number` in a form) while still using the database's atomic auto-increment as the safe sequence source, per the "never generate identifiers by counting existing records" rule.
**Date**: 2026-07-29
**Affected Module**: app/Models/Customer.php (same pattern will be reused for Order and Payment numbers in Phase 2/4)

### Decision 14: Service pricing is global with per-location override table
**Decision**: `services` table holds one global catalog row per service; `service_prices` is a separate pivot table for location-specific price overrides (nullable, opt-in per location).
**Reason**: Avoids duplicating full service rows per location just to change a price. `Service::priceForLocation()` resolves override-or-base price.
**Date**: 2026-07-29
**Affected Module**: app/Models/Service.php, app/Models/ServicePrice.php

## Phase 2 Decisions

### Decision 15: Generic number_sequences table for all business identifiers
**Decision**: Built a single reusable `number_sequences` table + `SequenceGenerator` service (row-locked, transactional atomic increment) rather than deriving order numbers from the auto-increment `id` like Customer numbers.
**Reason**: Order numbers reset per calendar year (`LND-2026-000001`, then `LND-2027-000001`), which an id-based scheme can't do. The same service will generate `PAY-YYYY-000001` payment references in Phase 4.
**Date**: 2026-07-29
**Affected Module**: app/Services/SequenceGenerator.php, app/Models/Order.php
**Trade-off**: One extra DB round-trip (transaction + row lock) per order/payment creation; acceptable for the volumes involved.

### Decision 16: bcmath for all order money arithmetic
**Decision**: All subtotal/discount/tax/total math in OrderService uses PHP's bcmath functions (bcadd, bcmul, bcsub, bcdiv, bccomp) operating on string decimals, never float arithmetic.
**Reason**: Floats cannot represent decimal currency exactly (e.g. 0.1 + 0.2 != 0.3), which is unacceptable for financial totals. bcmath guarantees exact decimal precision.
**Date**: 2026-07-29
**Affected Module**: app/Services/OrderService.php
**Trade-off**: Slightly more verbose than native operators; worth it for correctness.

### Decision 17: Rush fee is not an automatic surcharge
**Decision**: The `rush` boolean on an order is informational/workflow-only; rush pricing is applied by staff explicitly adding a Rush service line item (ServiceCategory::RUSH), not an automatic percentage multiplier.
**Reason**: Master spec lists "Same-Day Rush" / "Next-Day Rush" as services with their own price, not a formula. Avoids inventing an untested/undocumented business rule for how much a rush surcharge should be.
**Date**: 2026-07-29
**Affected Module**: app/Models/Order.php, OrderService
**How to apply**: If the business wants automatic rush pricing later, add it as an explicit, testable rule — don't infer a percentage.

### Decision 18: Discount reduces the taxable base proportionally
**Decision**: When a discount is applied, tax is computed on `taxable_subtotal - (taxable_subtotal * discount / subtotal)`, i.e. the discount is spread proportionally across taxable and non-taxable lines rather than applied only to one bucket.
**Reason**: Matches standard US retail tax practice (tax is charged on the net amount actually paid for taxable goods) and avoids over- or under-taxing when an order mixes taxable and non-taxable services.
**Date**: 2026-07-29
**Affected Module**: app/Services/OrderService.php

## Phase 3 Decisions

### Decision 19: Garment intake flags stored as JSON on orders, not a separate table
**Decision**: `garment_flags` (tear, missing_button, broken_zipper, color_bleed_risk, delicate_fabric, items_in_pockets) is a single JSON column on `orders` rather than a normalized `order_garment_flags` table.
**Reason**: These are simple booleans checked once at intake and never queried individually in reports; a JSON column avoids six extra nullable boolean columns (or a join) for data that's always read/written as a whole with the order.
**Date**: 2026-07-29
**Affected Module**: orders migration, app/Models/Order.php
**Trade-off**: Can't index or query on individual flags. Acceptable — no requirement surfaced for "find all orders with a broken zipper" reporting.

### Decision 20: Intake photos stored on the private `local` disk, served through a policy-checked route
**Decision**: `OrderPhoto` files are saved via `storeAs()` with a UUID filename (never the original) under `storage/app/private/order-photos/{order_id}/`, and are only readable through `OrderPhotoController@show`, which re-checks `OrderPolicy::view` before streaming.
**Reason**: Master spec requires private storage, random filenames, and controller-mediated access for intake photos — direct public URLs would leak customer photos to anyone with a guessed link.
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/OrderPhotoController.php, config/filesystems.php (local disk, unchanged default)

### Decision 21: `accepted_if` instead of separate `required_if` + `accepted` for override confirmation
**Decision**: The override confirmation checkbox uses the single `accepted_if:override,1` rule rather than `required_if:override,1` combined with `accepted`.
**Reason**: `accepted` is one of Laravel's *implicit* validation rules — it runs even when the field is completely absent from the request, so pairing it with `required_if` still failed validation on every non-override request (a real bug caught by Phase 3 tests). `accepted_if` bakes the condition into the rule itself and only fires when the condition is met.
**Date**: 2026-07-29
**Affected Module**: app/Http/Requests/UpdateOrderStatusRequest.php

### Decision 22: Enabled the `gd` PHP extension in the local dev environment
**Decision**: Uncommented `extension=gd` in the project's PHP installation's `php.ini` (`C:\Users\devuser\php\php.ini`).
**Reason**: Laravel's `UploadedFile::fake()->image()` test helper requires GD to generate in-memory test images; without it every photo-upload test errored with "GD extension is not installed." GD is also the standard extension for any future image processing (thumbnails, re-encoding) needed for uploaded photos.
**Date**: 2026-07-29
**Affected Module**: PHP environment configuration (outside the repo), not app code.

## Phase 4 Decisions

### Decision 23: Client-supplied idempotency key prevents duplicate payment submission
**Decision**: `StorePaymentRequest` requires an `idempotency_key` (a UUID rendered as a hidden field when the order page loads); `Payment.idempotency_key` is a unique column, and `PaymentService::recordPayment()` checks for an existing row with that key before proceeding.
**Reason**: Cashiers double-clicking "Record Payment" or a network retry must not create two payments for one cash transaction. The key is generated once per page load, so a re-submit of the same form (double-click, back-button resubmit) reuses the same key and is rejected.
**Date**: 2026-07-29
**Affected Module**: app/Services/PaymentService.php, app/Http/Requests/StorePaymentRequest.php
**Trade-off**: A page refresh generates a new key, so this doesn't protect against a deliberately-repeated manual payment — that's an intended behavior (staff can legitimately record two separate cash payments).

### Decision 24: Overpayment is prevented, not converted to store credit
**Decision**: `PaymentService::recordPayment()` rejects any payment amount greater than the order's current `balance_due`. There is no automatic store-credit/gift-balance creation for excess payment.
**Reason**: Store credit and gift cards are explicitly out of MVP scope (Section 16). Prevention is the simpler, safe default; credit-handling can be added later as its own feature with its own ledger.
**Date**: 2026-07-29
**Affected Module**: app/Services/PaymentService.php

### Decision 25: Void vs. Refund are distinct operations with different authorization and effects
**Decision**: `void` is for a payment recorded in error (wrong amount, wrong order) — it fully reverses the payment and requires Owner/Manager. `refund` is for money actually returned to a customer after the fact, supports partial amounts, and produces its own immutable `Refund` record while leaving the original `Payment` row intact (marked refunded/partially_refunded).
**Reason**: These represent different real-world events with different audit trails: a void says "this payment never should have counted"; a refund says "this payment happened and some/all of it was later returned." Conflating them would lose that distinction in reporting and audit logs.
**Date**: 2026-07-29
**Affected Module**: app/Services/PaymentService.php, app/Models/Payment.php, app/Models/Refund.php

### Decision 26: Row locking on Order/Payment during financial transactions
**Decision**: `PaymentService` re-fetches `Order`/`Payment` with `lockForUpdate()` inside each transaction before mutating balances, rather than trusting the model instance passed in.
**Reason**: Two concurrent payment requests against the same order (e.g. two staff members recording payment simultaneously) must not both read a stale `balance_due` and both succeed, causing an undetected overpayment. Row locking under MySQL serializes these; SQLite (used in tests) treats it as a no-op, which is acceptable since tests run single-threaded.
**Date**: 2026-07-29
**Affected Module**: app/Services/PaymentService.php

## Phase 5 Decisions

### Decision 27: Delivery status is decoupled from Order status
**Decision**: Marking a `Delivery` as `en_route` or `completed` does not automatically transition the parent `Order`'s status (e.g. to `out_for_delivery`/`completed`). Staff transition the order separately via the existing `OrderController@updateStatus`.
**Reason**: Coupling the two would require `OrderStatusTransitions` to special-case delivery-driven transitions and risks an order being auto-completed before staff have verified the order itself (payment collected, quality checked) is actually done. Keeping them independent is simpler and safer; the order status remains a deliberate staff action.
**Date**: 2026-07-29
**Affected Module**: app/Services/DeliveryService.php
**How to apply**: If auto-sync is wanted later, add it as an explicit opt-in rule with its own tests — don't infer it.

### Decision 28: Delivery address is snapshotted, not a live reference to Customer
**Decision**: `deliveries.address/city/state/zip` are copied from the customer at scheduling time rather than joined live from `customers`.
**Reason**: If a customer's address changes after a delivery is scheduled, the already-dispatched delivery should still show the address the driver was given, not a silently-changed one.
**Date**: 2026-07-29
**Affected Module**: database/migrations/..._create_deliveries_table.php

## Phase 6 Decisions

### Decision 29: SECURITY FIX — `isAdmin()` was wrong for location data-scoping; introduced `scopedLocationId()`
**Decision**: Replaced `if (!$user->isAdmin()) { $query->where('location_id', $user->location_id); }` (and its create/store equivalents) across CustomerController, OrderController, DeliveryController, DeliveryZoneController, PaymentController, AuditLogController, DashboardController, and ReportController with `$user->scopedLocationId()`, a new `User` model method that returns `null` only for the `OWNER` role (matching `canAccessLocation()`'s existing per-record check) and the user's own `location_id` otherwise.
**Reason**: `UserRole::isAdmin()` returns true for both `OWNER` and `MANAGER` — correct for *permission* checks (who can create users, edit locations) but wrong for *data scoping*. Because every list/index endpoint since Phase 1 used `isAdmin()` to decide whether to filter by location, a Manager (who is location-scoped per `canAccessLocation()`) could see every location's customers, orders, deliveries, payments, audit logs, and dashboard/report totals in list views — even though opening an individual record from another location correctly returned 403. This was a real cross-location data leak for the Manager role, caught while writing Phase 6's "Sales by Location" test (a Manager saw other locations' aggregate sales).
**Date**: 2026-07-29
**Affected Module**: app/Models/User.php (new `scopedLocationId()`), and every controller listed above
**How to apply**: Any *new* list/index/report query scoped by location must use `$user->scopedLocationId()`, never `$user->isAdmin()`. `isAdmin()` remains correct for CRUD permission checks (can this role create/edit X) — it was never wrong there.
**Verification**: Added regression tests (`test_manager_customer_list_excludes_other_locations`, `test_manager_order_list_excludes_other_locations`, `test_sales_by_location_only_shown_to_admin`) asserting a Manager's list views never contain another location's records.

### Decision 30: Cross-location aggregate reports ("Sales by Location") gated by `scopedLocationId() === null`, not a role check
**Decision**: The one report that intentionally spans all locations (`salesByLocation`) is shown only when `scopedLocationId()` returns null — i.e. Owner always, or any other role explicitly configured with no single assigned location (e.g. a business-wide Accountant).
**Reason**: Consistent with Decision 29 — "can see everything" is a property of *not being tied to one location*, not of a specific role name.
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/ReportController.php

## Phase 7 Decisions

### Decision 31: Inventory quantity is a derived running total, not computed from the transaction ledger on read
**Decision**: `inventory_items.current_quantity` is a stored, mutable column updated transactionally alongside each `inventory_transactions` row (which also stores a `quantity_after` snapshot), rather than always summing the ledger on every read.
**Reason**: Matches the pattern used for `orders.balance_due`/`amount_paid` (Phase 4) — fast reads for the common case (checking stock level), while the immutable ledger remains available for audit/history. `quantity_after` on each transaction lets the history view show the running balance without recomputation, and lets a future audit re-derive the current value from the ledger if the two ever need to be reconciled.
**Date**: 2026-07-29
**Affected Module**: app/Services/InventoryService.php, app/Models/InventoryItem.php

### Decision 32: Suppliers can be shared across all locations (nullable location_id)
**Decision**: `suppliers.location_id` is nullable; null means the supplier serves all locations rather than being tied to one.
**Reason**: A single business commonly buys detergent/supplies from the same vendor across every location. Forcing a location per supplier would require duplicate supplier records for a multi-location business.
**Date**: 2026-07-29
**Affected Module**: database/migrations/..._create_suppliers_table.php

### Decision 33: Expenses are create-only — no update or delete routes
**Decision**: Following the same immutability principle already applied to Payments/Refunds (Phase 4), `Expense` records can be created but never edited or deleted through the UI.
**Reason**: An expense is a financial record; silently editing a past expense after it may have been included in a report breaks the audit trail. A data-entry mistake should be corrected with a new adjusting entry, not a silent edit — consistent with "never permanently delete financial records."
**Date**: 2026-07-29
**Affected Module**: app/Policies/ExpensePolicy.php (update/delete always return false), routes/web.php

## Phase 8 Decisions

### Decision 34: Tracking token is a random string, not derivable from order_number or id
**Decision**: `orders.tracking_token` is 40 random characters (`Str::random(40)`), generated once in the model's `created` event, completely independent of `order_number` (LND-YYYY-NNNNNN) or the primary key.
**Reason**: `order_number` is sequential and guessable (LND-2026-000042 implies 000041, 000043 exist) — using it as the public lookup key would let anyone enumerate other customers' orders by incrementing the number. A long random token makes guessing infeasible, and is combined with rate limiting on the route as defense in depth.
**Date**: 2026-07-29
**Affected Module**: app/Models/Order.php, migration adding tracking_token

### Decision 35: SMS provider abstraction ships with a logging-only default, not a real SMS vendor
**Decision**: `App\Services\Sms\SmsProvider` is an interface; the only implementation is `LogSmsProvider`, which logs a masked phone number and message instead of sending anything. It's bound in `AppServiceProvider::register()`.
**Reason**: No SMS vendor credentials (Twilio, etc.) exist yet, and adding one is an external-service integration decision the business needs to make (cost, vendor choice) — not something to guess at. The abstraction means swapping in a real provider later is a one-line binding change with no changes to `OrderStatusUpdated` or `SmsChannel`.
**Date**: 2026-07-29
**Affected Module**: app/Services/Sms/, app/Providers/AppServiceProvider.php
**How to apply**: When a real SMS vendor is chosen, implement `SmsProvider::send()` against that vendor's API and change the `bind()` call — nothing else needs to change.

### Decision 36: Customer notifications sent only for customer-relevant status transitions
**Decision**: `OrderStatusService::transition()` only dispatches `OrderStatusUpdated` when the new status is `ready_for_pickup`, `out_for_delivery`, or `completed` — not for every internal step (tagged, sorting, washing, drying, finishing, quality_check).
**Reason**: A customer doesn't need (and would find it spammy) to be notified that their laundry moved from "sorting" to "washing." They care about pickup-readiness, delivery, and completion.
**Date**: 2026-07-29
**Affected Module**: app/Services/OrderStatusService.php

### Decision 37: Notification preferences are per-channel booleans on Customer, reusing existing consent fields for the "should we contact them at all" gate
**Decision**: `notify_email` (default true) and `notify_sms` (default false) control which channels `OrderStatusUpdated::via()` returns. These are separate from `operational_consent`/`marketing_consent` (Phase 1) — the notify_* flags are the channel preference, the consent flags remain the legal/marketing distinction.
**Reason**: SMS costs money per message and requires clearer opt-in than email, hence the differing defaults. Keeping these separate from marketing consent avoids conflating "can we email you a receipt" with "can we send you promotional texts."
**Date**: 2026-07-29
**Affected Module**: app/Models/Customer.php, migration adding notify_email/notify_sms

## Phase 9 Decisions

### Decision 38: PaymentProvider ships with a stub implementation, not a real vendor integration
**Decision**: `App\Services\OnlinePayment\PaymentProvider` is an interface; `StubPaymentProvider` (bound by default in `AppServiceProvider`) simulates a hosted checkout with a local route showing Success/Fail buttons that POST HMAC-signed webhook payloads — no real card processor is integrated.
**Reason**: Same reasoning as the SMS provider (Decision 35) — no vendor (Stripe, Square, etc.) has been chosen, and picking one is a business/cost decision, not a technical default to guess at. The interface is designed around what any hosted-checkout vendor actually provides (create session → redirect → webhook confirms), so swapping in a real vendor later means writing one class against `PaymentProvider` and changing one `bind()` call — no changes to `PaymentService`, `OnlinePaymentController`, or `PaymentWebhookController`.
**Date**: 2026-07-29
**Affected Module**: app/Services/OnlinePayment/, app/Providers/AppServiceProvider.php
**How to apply**: Never build a real integration by modifying the stub in place — implement a new class against the interface and change the binding.

### Decision 39: The webhook is the only path that can mark an online payment complete
**Decision**: `PaymentService::confirmOnlinePaymentSucceeded()` is called exclusively from `PaymentWebhookController`, never from the browser-facing checkout-return flow. `OnlinePaymentController@store` only creates a `pending` Payment and redirects to checkout; it never marks anything paid.
**Reason**: Directly required by the master spec — "Do not mark an order as paid only because the browser returned to a success URL." A customer closing the tab, a network blip on redirect, or a malicious replay of a success URL must never be sufficient to mark an order paid; only a signature-verified, server-to-server event is authoritative.
**Date**: 2026-07-29
**Affected Module**: app/Services/PaymentService.php, app/Http/Controllers/OnlinePaymentController.php, app/Http/Controllers/PaymentWebhookController.php

### Decision 40: online_card is excluded from the manual payment-recording endpoint
**Decision**: `StorePaymentRequest`'s `method` validation is an explicit allow-list of `cash` and `external` only — `online_card` is not accepted there even though it's a valid `PaymentMethod` case.
**Reason**: Manually recording a payment as "online_card, completed" would let staff mark an order paid without any actual card charge ever happening, defeating the entire point of provider-verified payments. `online_card` payments may only be created via `PaymentService::initiateOnlineCheckout()` (starts `pending`) and completed only by `confirmOnlinePaymentSucceeded()` (webhook-driven).
**Date**: 2026-07-29
**Affected Module**: app/Http/Requests/StorePaymentRequest.php

### Decision 41: Webhook signature and idempotency key are the same field (provider_transaction_id), payload transport is POST fields, not header+raw-body
**Decision**: The stub webhook reads `payload` and `signature` from POST body fields (`$request->input(...)`), rather than a raw request body plus a custom signature header (how Stripe/most real vendors do it).
**Reason**: An HTML form (used by the stub's simulated checkout page) can't set custom HTTP headers or submit a raw non-form body — POST fields are what's available for a same-app-rendered simulation. The cryptographic verification itself (`hash_hmac` + `hash_equals`) is identical to what a real integration would do; only the transport detail differs for the stub's convenience UI. A real vendor's controller would read its header/raw-body per that vendor's docs and call the same `verifyWebhookSignature()`/`parseWebhookPayload()` methods.
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/PaymentWebhookController.php, app/Services/OnlinePayment/StubPaymentProvider.php

## Phase 10 Decisions — Final Hardening Review

An independent authorization/IDOR review (Explore agent, read-only) was run against the full codebase. Findings and fixes:

### Decision 42: FIXED (CRITICAL) — Manager could self-promote to Owner or create Owner/Manager accounts
**Finding**: `StoreUserRequest`/`UpdateUserRequest` validated `role` against the full `UserRole` enum with no ceiling, and `UserPolicy::create`/`update` only required `isAdmin()` (true for Manager). A Manager could `PATCH` their own user record with `role=owner`, or create a brand-new Owner account outright.
**Fix**: Both FormRequests now reject a `role` of `owner` or `manager` in the payload unless the acting user is already an Owner (checked via `withValidator`). Editing one's own profile without changing the existing role is still allowed (compares against the *current* role, not a blanket ban) so Managers can still update their own name/email.
**Date**: 2026-07-29
**Affected Module**: app/Http/Requests/Admin/StoreUserRequest.php, UpdateUserRequest.php

### Decision 43: FIXED (HIGH) — Admin user list and locations list had no location scoping
**Finding**: `Admin\UserController::index()`/`edit()` and `Admin\LocationController::index()` returned every user/location in the business regardless of the viewer's assigned location — the same bug class fixed for other controllers in Phase 6 (Decision 29), missed here because these are "admin" routes.
**Fix**: Both now filter by `scopedLocationId()`, consistent with every other list view in the app. `LocationPolicy::viewAny` tightened from `true` to `isAdmin()` (previously any authenticated user, including Cashier/Driver, could load `/admin/locations`).
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/Admin/UserController.php, app/Http/Controllers/Admin/LocationController.php, app/Policies/LocationPolicy.php

### Decision 44: FIXED (HIGH) — Audit log viewer had no role restriction
**Finding**: `AuditLogController::index()` scoped by location but had no role check at all — any authenticated Cashier, Laundry Staff, or Driver could read the full audit trail for their location, including refund/void reasons, role-change history, and old/new field values for customer records.
**Fix**: Restricted to Owner/Manager/Accountant, matching the Reports page precedent from Phase 6. Nav link hidden for other roles too.
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/AuditLogController.php, resources/views/layouts/app.blade.php

### Decision 45: FIXED (MEDIUM) — IDOR in Business Settings via `?location_id=`
**Finding**: `BusinessSettingsController::index()` authorized only at the class level (`viewAny` → `isAdmin()`) and then trusted a raw `?location_id=` query parameter with `findOrFail`, with no per-location check. A Manager could read another location's tax rate, hours, and terms notice by changing the query string.
**Fix**: The requested `location_id` is now resolved only from the set of locations the actor is already scoped to; anything else returns 403 before the per-record `view` policy check even runs.
**Date**: 2026-07-29
**Affected Module**: app/Http/Controllers/Admin/BusinessSettingsController.php

### Decision 46: FIXED (LOW) — Cross-location foreign keys validated only with `exists:`, not location-matched
**Finding**: `assigned_user_id` (order assignment), `driver_id` (delivery assignment/scheduling), `delivery_zone_id`, and `supplier_id` were validated with plain `exists:table,id`, allowing e.g. a delivery at Location A to be assigned to a driver who only works at Location B — a data-integrity/operational-confusion issue rather than a direct data leak.
**Fix**: These now use `Rule::exists(...)->where('location_id', ...)` scoped to the record's own location (suppliers additionally allow `location_id IS NULL` since they may be intentionally shared per Decision 32). `SupplierController::store()` also now forces `location_id` server-side for non-Owner actors, matching every other create endpoint.
**Date**: 2026-07-29
**Affected Module**: app/Http/Requests/AssignOrderRequest.php, ScheduleDeliveryRequest.php, StoreExpenseRequest.php, StoreInventoryItemRequest.php, app/Http/Controllers/DeliveryController.php, SupplierController.php, app/Policies/SupplierPolicy.php

### Decision 47: Session cookie security forced in production regardless of `.env` drift
**Decision**: `AppServiceProvider::boot()` now forces `session.secure`, `session.http_only`, and `session.same_site` to safe values whenever `APP_ENV=production`, in addition to the existing `URL::forceScheme('https')` call.
**Reason**: Defense in depth — a misconfigured or missing `SESSION_SECURE_COOKIE` env var in production should not silently downgrade cookie security.
**Date**: 2026-07-29
**Affected Module**: app/Providers/AppServiceProvider.php

### Decision 48: Rate limiting added to the entire authenticated route group
**Decision**: Added `throttle:120,1` to the whole `auth` middleware group in `routes/web.php`, on top of the existing per-route limits (login, password reset, public tracking, webhook).
**Reason**: Defense in depth against a compromised or scripted authenticated session hammering any endpoint. 120 requests/minute is generous enough not to interfere with normal staff usage (confirmed against the full test suite) while bounding automated abuse.
**Date**: 2026-07-29
**Affected Module**: routes/web.php

## Final Hardening Checklist Status

- [x] Authorization review (independent agent pass + manual follow-up fixes)
- [x] IDOR review (5 concrete findings, all fixed — see Decisions 42-46)
- [x] Validation review (mass-assignment allow-lists confirmed tight across all FormRequests)
- [x] Upload-security review (Phase 3 controls re-verified: random filenames, private disk, MIME/size/dimension limits)
- [x] Financial-integrity review (bcmath throughout, DB transactions with row locking on all money mutations, no hard-deletes of Payments/Refunds/Expenses)
- [x] Rate-limit review (login, password reset, public tracking, checkout, webhook, and now the full authenticated group)
- [x] Production configuration review (forced HTTPS + secure cookies in production, debug mode defaults false, dependency audits clean)
- [x] Backup and restore documentation (docs/BACKUP_RESTORE.md)
- [x] Dependency audit (`composer audit` and `npm audit` — zero vulnerabilities)
- [x] Full automated test suite (230 tests passing)

## Pending Decisions

(None — MVP scope complete through Phase 10)
