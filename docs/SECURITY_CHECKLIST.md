# Security Checklist

## Phase 0 - Project Foundation

### Authentication
- [ ] Secure password hashing (Laravel's bcrypt)
- [ ] Email verification for accounts
- [ ] Password reset with token expiry
- [ ] Session regeneration after login
- [ ] Session invalidation after logout
- [ ] Account active/inactive status
- [ ] Login attempt rate limiting (ThrottleRequests)
- [ ] Generic login failure messages (no user enumeration)
- [ ] Last successful login tracking
- [x] Application key configured (Laravel default)

### Password Policy
- [ ] Minimum 12 characters for staff
- [ ] Maximum 64+ character support
- [ ] No truncation of long passwords
- [ ] Current password required for sensitive changes
- [x] Laravel's default password validation

### Authorization
- [ ] Role-based access control (RBAC) via enums
- [ ] Laravel policies for all resources
- [ ] Authorization middleware on protected routes
- [ ] Server-side authorization on every action
- [ ] Least privilege principle implemented
- [x] Location-based access control (fixed Phase 6: `scopedLocationId()` replaces incorrect `isAdmin()` scoping that let Managers see cross-location data in list views — see DECISIONS.md #29)

### Session Security
- [ ] HTTPS forced in production (config)
- [ ] HTTP-only cookies
- [ ] SameSite cookie configuration
- [ ] CSRF protection on all forms
- [ ] Session timeout configured
- [ ] Logout from other sessions
- [ ] Session regeneration after role change

### Input & Output Security
- [ ] Form request validation on all inputs
- [ ] Allow-list validation for enums
- [ ] Output escaping in Blade templates
- [ ] Mass-assignment protection configured
- [x] File upload validation (Phase 3: order intake photos — MIME/extension allow-list, size cap, dimension bounds, random UUID filenames, private disk storage, policy-checked serving route)
- [ ] Pagination limits
- [ ] Sort column allow lists
- [ ] IDOR protection via policies

### Financial Security
- [x] Database transactions for financial operations (OrderService, PaymentService — all wrapped in DB::transaction with row locking)
- [x] Decimal money fields (no floats) — bcmath used throughout, decimal:2 casts
- [x] Audit logs for financial changes (PAYMENT_RECORDED, REFUND_ISSUED, order/payment UPDATED)
- [x] No deletion of financial records (Payments/Refunds never deleted — void/refund are additive status changes)
- [x] Price never trusted from client (OrderService computes all prices server-side from Service records)
- [x] Duplicate payment submission prevented (client idempotency_key, unique DB constraint)
- [x] Overpayment prevented (amount capped at order.balance_due)
- [x] Refund capped at payment's refundable balance

### Sensitive Information
- [ ] No SSN storage
- [ ] No full card numbers stored
- [ ] No CVV/CVC stored
- [ ] Audit log masks sensitive data
- [ ] Encryption for business config values (when needed)

### Logging & Monitoring
- [ ] Passwords never logged
- [ ] Tokens never logged
- [ ] Card numbers never logged
- [ ] Security events logged (login, role changes, overrides)
- [ ] Audit logs immutable
- [ ] Error handling doesn't leak paths or SQL

### Headers & Configuration
- [ ] Content-Security-Policy configured
- [ ] X-Content-Type-Options set
- [ ] Referrer-Policy set
- [ ] HSTS configured (after HTTPS verified)
- [ ] Debug mode disabled in production
- [ ] .env file not in git

### Database
- [ ] Foreign key constraints enforced
- [ ] Soft deletes where appropriate
- [ ] Indexes on frequently queried columns
- [ ] No hardcoded credentials

### Testing
- [ ] Authentication tests
- [ ] Authorization tests
- [ ] Rate limiting tests
- [ ] CSRF protection tests
- [ ] Input validation tests

## Phase 1+ Controls

- [x] Customer data privacy controls (notification preferences, consent fields)
- [x] Order financial integrity tests (Phase 2/4)
- [ ] Payment webhook signature verification (Phase 9)
- [ ] PCI compliance for payment handling (Phase 9)
- [x] Secure delivery tracking tokens (Phase 8: 40-char random token, independent of sequential order_number)
- [x] Rate limiting on public tracking (Phase 8: throttle:20,1 on /track/{token})

## Current Status

Phase 0 implementation in progress.
