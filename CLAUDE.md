# Laundry Management System - Project Guidelines

## Technology Stack

- **Framework**: Laravel 13.x
- **PHP**: 8.4+
- **Database**: MySQL (SQLite for testing)
- **Frontend**: Laravel Blade + Bootstrap 5 + Gentelella
- **Build**: Vite
- **Testing**: PHPUnit
- **Authentication**: Laravel's built-in system

## Coding Conventions

- PSR-12 coding standards
- Use models with relationships
- Use form requests for validation
- Use policies for authorization
- Use events for audit logging
- Decimal columns for all money and weight
- Store timestamps in UTC
- Use database transactions for financial operations

## Security Rules - MANDATORY

- Never trust user input for prices, weights, or financial calculations
- Always validate and authorize on the server side
- Never store full card numbers, CVV, or raw banking credentials
- Never log passwords, tokens, or sensitive payment data
- Use parameterized queries (Eloquent) exclusively
- Apply CSRF protection to all forms
- Require HTTPS in production
- Use HTTP-only, Secure, SameSite cookies
- Implement rate limiting on sensitive endpoints
- Never reveal whether an email exists during password reset
- Session regeneration after login and privilege changes

## Files Claude Must Inspect First

1. `CLAUDE.md` - this file
2. `docs/PROJECT_STATUS.md` - current progress and known issues
3. `docs/DATABASE_MAP.md` - table and enum structure
4. `docs/ROUTES_MAP.md` - route definitions
5. `docs/SECURITY_CHECKLIST.md` - security control status
6. `docs/DECISIONS.md` - important architectural decisions
7. `docs/NEXT_TASK.md` - exact next task for new sessions

## Directories Claude Should Ignore

- `vendor/` - composer packages
- `node_modules/` - npm packages
- `storage/` - application logs and cache
- `public/` - compiled assets and uploads
- `bootstrap/cache/` - Laravel bootstrap cache

## Key Concepts

- **Order**: An order is immutable once created. Status changes create history records.
- **Location**: Single business may have multiple locations. All operational data is location-aware.
- **Audit Log**: Every financial and permission-related change is recorded.
- **Payment**: Always process through PCI-compliant payment provider or record cash manually.
- **Tax**: Configurable per location and service.

## Testing Command

```bash
php artisan test --filter Phase0
```

Run full suite before completing a phase:
```bash
php artisan test
```

## US Business Defaults

- Currency: USD
- Locale: en-US
- Default timezone: America/New_York
- Date format: MM/DD/YYYY
- Time format: 12-hour with AM/PM
- Weight unit: pounds
- Distance unit: miles

All timestamps stored in UTC, displayed in business timezone.
