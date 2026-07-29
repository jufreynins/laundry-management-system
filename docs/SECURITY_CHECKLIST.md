# Security Checklist

Status as of Phase 10 (final hardening). All items below were implemented across Phases 0-10; see docs/DECISIONS.md for the reasoning behind each.

### Authentication
- [x] Secure password hashing (Laravel bcrypt via `hashed` cast)
- [x] Email verification support (Laravel's built-in `email_verified_at`, verification not yet enforced on login — acceptable for MVP single-business deployment)
- [x] Password reset with token expiry (Laravel's default broker + `password_reset_tokens` table)
- [x] Session regeneration after login (`LoginController::store`)
- [x] Session invalidation after logout (`LoginController::destroy`)
- [x] Account active/inactive status (`users.active`, checked at login)
- [x] Login attempt rate limiting (custom per-email+IP limiter, 5/min, plus route-level `throttle:10,1`)
- [x] Generic login failure messages (no user enumeration)
- [x] Last successful login tracking (`users.last_login_at`)
- [x] Application key configured

### Password Policy
- [x] Minimum 12 characters for staff (enforced in StoreUserRequest/UpdateUserRequest and password reset)
- [x] Maximum 64+ characters supported, no truncation
- [x] Compromised-password check (`Password::uncompromised()`, disabled only in the test environment)
- [x] Current password not required for admin-driven changes (acceptable — Owner/Manager resetting another user's account is an administrative action, not a self-service change)

### Authorization
- [x] Role-based access control via `UserRole` enum + `hasRole()`/`isAdmin()`
- [x] Laravel policies for every model with sensitive data
- [x] Server-side authorization on every write action (FormRequest::authorize() or controller-level $this->authorize())
- [x] Least privilege — verified via Phase 10 independent review (see DECISIONS.md #42-46)
- [x] Location-based access control via `User::scopedLocationId()` (only Owner bypasses; see DECISIONS.md #29, #42-46 for bugs found and fixed)
- [x] Privilege-escalation prevention: non-Owner cannot create/promote Owner or Manager accounts (DECISIONS.md #42)

### Session Security
- [x] HTTPS forced in production (`URL::forceScheme('https')`)
- [x] HTTP-only cookies (forced in production regardless of .env, DECISIONS.md #47)
- [x] SameSite cookie configuration (`lax`, forced in production)
- [x] CSRF protection on all forms (default Laravel middleware; only the payment webhook is exempted, and only that one route — DECISIONS.md #41)
- [x] Session timeout configured (120 min default, `SESSION_LIFETIME`)
- [ ] "Logout from other sessions" UI — not built; out of MVP scope, single-session-per-user assumption is acceptable for a small staff team

### Input & Output Security
- [x] Form request validation on all state-changing inputs
- [x] Allow-list validation for enums (`Rule::in`, `new Enum(...)`)
- [x] Output escaping in Blade templates (no `{!! !!}` used anywhere in the app)
- [x] Mass-assignment protection (every model's `$fillable` reviewed; system-generated fields like `customer_number`/`order_number`/`tracking_token` are deliberately excluded and set via `forceFill`)
- [x] File upload validation (Phase 3: intake photos — MIME/extension allow-list, size cap, dimension bounds, random UUID filenames, private disk, policy-checked serving route)
- [x] Pagination limits (`paginate(20)` on every growing list; small bounded lists like delivery zones/suppliers use `get()`)
- [x] Sort column allow lists — not applicable; no user-controlled sort column exists anywhere in the app
- [x] IDOR protection via policies — independently reviewed in Phase 10; all findings fixed (DECISIONS.md #42-46)

### Financial Security
- [x] Database transactions for financial operations, with row locking (`lockForUpdate()`) on Order/Payment during money mutations
- [x] Decimal money fields, bcmath arithmetic throughout (no floats)
- [x] Audit logs for financial changes (PAYMENT_RECORDED, REFUND_ISSUED, ROLE_CHANGED, OVERRIDE_STATUS, etc.)
- [x] No deletion of financial records (Payments/Refunds/Expenses have no update or delete routes — reversal via void/refund only)
- [x] Price never trusted from client (OrderService computes all prices server-side from Service records)
- [x] Duplicate payment submission prevented (client idempotency_key, unique DB constraint)
- [x] Overpayment prevented (amount capped at order.balance_due)
- [x] Refund capped at payment's refundable balance
- [x] Online payments confirmed only via signature-verified webhook, never the browser return URL (DECISIONS.md #39)

### Sensitive Information
- [x] No SSN storage
- [x] No full card numbers, CVV/CVC, or magnetic-stripe data stored (hosted-checkout architecture — Phase 9)
- [x] Only provider-supplied metadata stored for online payments (brand, last four, receipt URL)
- [x] Public tracking page exposes only customer-safe fields (Phase 8) — verified by test (`test_tracking_page_does_not_expose_customer_pii`)
- [ ] Encryption for business config values — `business_settings.encrypted` column exists but no setting currently requires it; implement when a genuinely sensitive config value (e.g. a provider API key stored in DB rather than .env) is added

### Logging & Monitoring
- [x] Passwords never logged (Laravel's exception handler redacts by default; no custom logging of request bodies)
- [x] Payment/card data never logged (LogSmsProvider masks phone numbers; webhook payloads don't include card data since none is ever received)
- [x] Security events logged via AuditLog: login, login_failed, password_changed, role_changed, payment_recorded, refund_issued, override_status
- [x] Audit logs immutable (no update/delete routes; `AuditLog` has no timestamps update path)
- [x] Error handling doesn't leak paths/SQL — `APP_DEBUG` defaults false, generic error pages in production

### Headers & Configuration
- [x] Content-Security-Policy configured (`SecurityHeaders` middleware — `script-src 'self'`, no unsafe-inline for scripts)
- [x] X-Content-Type-Options: nosniff
- [x] Referrer-Policy: strict-origin-when-cross-origin
- [x] X-Frame-Options: DENY (CSP frame-ancestors also set to 'none')
- [x] Permissions-Policy restricting geolocation/microphone/camera
- [x] HSTS (Strict-Transport-Security) set when request is secure or environment is production
- [x] Debug mode disabled by default (`APP_DEBUG` defaults to false)
- [x] .env file not in git (`.gitignore` default, verified)

### Database
- [x] Foreign key constraints enforced on every relationship (restrict/cascade/set null chosen per relationship semantics)
- [x] Soft deletes — not used; financial/audit tables use status-based reversal instead (void/refund), which is the stronger guarantee for those tables specifically
- [x] Indexes on frequently queried columns (location_id+status, location_id+created_at, etc. — see docs/DATABASE_MAP.md)
- [x] No hardcoded credentials (all via .env/config)

### Testing
- [x] Authentication tests (Phase 0)
- [x] Authorization tests (every phase; Phase 10 adds dedicated IDOR/privilege-escalation regression tests)
- [x] Rate limiting tests (login, public tracking)
- [x] CSRF protection — implicitly covered (Laravel's test client enforces it by default except where explicitly exempted)
- [x] Input validation tests (every FormRequest has corresponding failure-case tests)
- [x] 230 tests passing as of Phase 10

## Payment & PCI Controls (Phase 9)
- [x] Payment webhook signature verification (HMAC-SHA256, `hash_equals` constant-time comparison, rejects invalid/missing signatures with 400)
- [x] PCI compliance via hosted-checkout architecture (`PaymentProvider` abstraction) — this app never collects or stores raw card numbers, CVV, or magnetic-stripe data
- [x] Webhook idempotency (provider_transaction_id uniqueness + status check)

## Privacy & Public-Facing Controls (Phase 8)
- [x] Customer notification preferences (notify_email/notify_sms, separate from marketing/operational consent)
- [x] Secure delivery/order tracking tokens (40-char random, independent of sequential order_number)
- [x] Rate limiting on public tracking (throttle:20,1)
- [x] Public tracking page never exposes PII, internal notes, photos, or DB IDs

## Known Gaps / Accepted Risk (documented, not fixed — out of MVP scope)
- No "logout from other sessions" self-service UI (see Session Security above)
- No enforced email verification gate on login (email_verified_at exists but isn't checked)
- No encrypted business settings in use yet (column ready, no current value needs it)
