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
- [ ] Location-based access control

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
- [ ] File upload validation (when implemented)
- [ ] Pagination limits
- [ ] Sort column allow lists
- [ ] IDOR protection via policies

### Financial Security
- [ ] Database transactions for financial operations
- [ ] Decimal money fields (no floats)
- [ ] Audit logs for financial changes
- [ ] No deletion of financial records (reversal only)
- [ ] Price never trusted from client

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

- [ ] Customer data privacy controls
- [ ] Order financial integrity tests
- [ ] Payment webhook signature verification
- [ ] PCI compliance for payment handling
- [ ] Secure delivery tracking tokens
- [ ] Rate limiting on public tracking

## Current Status

Phase 0 implementation in progress.
