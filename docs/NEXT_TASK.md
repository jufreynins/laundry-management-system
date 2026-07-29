# Next Task

## For Next Session

**Phase 1: Customers and Services**

### What to Do
1. Read CLAUDE.md, PROJECT_STATUS.md, DATABASE_MAP.md, DECISIONS.md
2. Create Customer model and migration
   - name (required)
   - email (unique, nullable)
   - phone (US format)
   - address, city, state, zip (optional)
   - location_id (required, belongs to location)
   - preferences (json: communication_consent, marketing_consent)
   - active (boolean)
3. Create Service model and migration
   - name (e.g., "Wash and Fold")
   - category (enum: wash_fold, dry_clean, alterations, etc.)
   - pricing_type (enum: per_pound, per_item, flat_fee, hourly, custom)
   - base_price (decimal 10,2)
   - minimum_charge (decimal 10,2, nullable)
   - taxable (boolean)
   - estimated_duration (integer minutes)
   - location_id (nullable for location-specific overrides)
   - active (boolean)
4. Create LocationService (pivot) if location-specific pricing needed
5. Create CustomerPolicy for authorization
6. Create ServicePolicy for authorization
7. Create CustomerController and ServiceController (CRUD)
8. Create validation forms (CustomerFormRequest, ServiceFormRequest)
9. Create Blade views for CRUD (customers, services)
10. Create comprehensive tests for:
    - Customer creation and retrieval
    - Customer authorization (only own location can access)
    - Service creation and pricing
    - Location-specific service pricing
    - Duplicate customer name warning (query from same location)
    - Service validation (price > 0, etc.)
11. Run tests: `php artisan test --filter Phase1`
12. Verify all tests pass
13. Update PROJECT_STATUS.md

### Critical Files to Check First
- `CLAUDE.md` - project rules and conventions
- `docs/DATABASE_MAP.md` - table structure
- `docs/DECISIONS.md` - architectural decisions

### Stop When
- All Phase 1 tests pass
- Customers can be created and edited per location
- Services can be created with multiple pricing types
- Authorization prevents cross-location customer access
- Duplicate customer detection works

### Do NOT Start Phase 2
Do not implement orders until Phase 1 is complete and thoroughly tested.
