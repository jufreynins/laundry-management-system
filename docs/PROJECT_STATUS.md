# Laundry Management System - Project Status

## Current Phase

**All 10 phases COMPLETED.** MVP (Section 16 of the master spec) is fully built and hardened.

**Phase 10: Final Hardening** (COMPLETED) — independent authorization/IDOR review, 5 findings fixed (see DECISIONS.md #42-46), production config hardening, backup/restore docs, dependency audits clean
**Phase 9: Hosted Online Payment** (COMPLETED)
**Phase 8: Public Tracking and Notifications** (COMPLETED)
**Phase 7: Inventory and Expenses** (COMPLETED)
**Phase 6: Dashboard and Reports** (COMPLETED — also fixed a cross-location data-scoping bug affecting Phases 1-5, see DECISIONS.md #29)
**Phase 5: Pickup and Delivery** (COMPLETED)
**Phase 4: Payments and Receipts** (COMPLETED)
**Phase 3: Production Workflow** (COMPLETED)
**Phase 2: Order Intake** (COMPLETED)
**Phase 1: Customers and Services** (COMPLETED)
**Phase 0: Project Foundation** (COMPLETED, backfilled with auth/layout/settings)

## Completed Modules

- [x] Authentication (login, logout, password reset, rate limiting, generic failure messages)
- [x] Role-based + location-based authorization (`UserRole` enum, `User::scopedLocationId()`)
- [x] Business/location settings, audit log foundation
- [x] Customer management (search, duplicate detection, notification preferences)
- [x] Service catalog with 5 pricing types + location-specific price overrides
- [x] Order intake with server-side pricing (never trusts client-submitted prices), bcmath decimal math
- [x] Production workflow: centralized status transition map, owner-authorized overrides, garment intake flags, private intake photo storage
- [x] Payments: cash/external/online, partial payments, void, refund, receipts, claim tickets
- [x] Pickup/delivery scheduling, driver assignment, mobile driver view
- [x] Dashboard + reports (orders today, revenue, sales by service/location, tax summary, payment summary)
- [x] Inventory (stock ledger, reorder threshold) + expenses (create-only, immutable)
- [x] Public order tracking (random token, customer-safe fields only, rate limited) + email/SMS notifications
- [x] Hosted online payment via PaymentProvider abstraction, signed webhooks, idempotent processing
- [x] Final hardening: IDOR review, privilege-escalation fixes, production security config, backup/restore docs

## Test Suite

**230 tests passing** across 10 phase-organized test directories (`tests/Feature/Phase0` through `Phase10`).

```bash
php artisan test
```

## Known Issues / Accepted Gaps (documented in SECURITY_CHECKLIST.md)

- No "logout from other sessions" self-service UI (out of MVP scope)
- Email verification column exists but isn't enforced at login (acceptable for a small internal staff team)
- `business_settings.encrypted` column exists but nothing currently needs it

## Environment

- PHP: 8.4, Laravel: 13.x
- Database: SQLite for testing, MySQL for production
- Composer/npm dependency audits: clean (see DECISIONS.md Phase 10 section)

## What's NOT Built (explicitly out of MVP scope per master spec Section 16)

SaaS subscriptions, advanced accounting/payroll, machine IoT, native mobile app, route optimization, complex CRM, AI features, loyalty points, gift cards, real payment-vendor integration (StubPaymentProvider is the placeholder — see DECISIONS.md #38), advanced commercial contracts.

## Suggested Next Steps (beyond MVP, if the business wants to continue)

1. Choose and integrate a real payment vendor (Stripe/Square) against the existing `PaymentProvider` interface — no other code changes needed.
2. Choose and integrate a real SMS vendor against the existing `SmsProvider` interface.
3. Enforce email verification if/when the business wants self-service password resets to be fully trustworthy.
4. Add a "logout from other sessions" control if multi-device session hygiene becomes a concern.
