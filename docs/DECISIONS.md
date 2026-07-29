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

## Pending Decisions

(None at this phase)
