# Next Task

## Status

All 10 phases from the original master build plan are complete. The MVP defined in Section 16 is fully implemented, tested (230 tests passing), and hardened (Phase 10 IDOR/authorization review complete — see docs/DECISIONS.md #42-46).

**There is no queued next phase.** Future work should come from explicit user/business direction, not automatic continuation.

## If Asked to Continue Building

Read in this order before touching code:
1. `CLAUDE.md` — conventions and mandatory security rules
2. `docs/PROJECT_STATUS.md` — what exists today
3. `docs/DATABASE_MAP.md` — schema
4. `docs/DECISIONS.md` — why things are built the way they are (especially the Phase 6 and Phase 10 security-fix entries — don't reintroduce those bugs)
5. `docs/SECURITY_CHECKLIST.md` — current control status and accepted gaps

## Likely Next Real-World Requests (not yet built, all deliberately deferred)

- **Real payment vendor integration**: Implement `App\Services\OnlinePayment\PaymentProvider` against Stripe/Square/etc. and swap the `AppServiceProvider` binding. No controller or service changes needed — see DECISIONS.md #38.
- **Real SMS vendor integration**: Same pattern against `App\Services\Sms\SmsProvider` — see DECISIONS.md #35.
- **Email verification enforcement**: The column exists; add the `MustVerifyEmail` contract to `User` and route middleware if the business wants it.
- **Gift cards / store credit**: Explicitly out of MVP scope (Section 16). Would need its own ledger, separate from the current overpayment-prevention design (DECISIONS.md #24).
- **Advanced commercial accounts**: Section 16 excludes this from MVP; `intake_channel` already has a `commercial` value as a placeholder but no dedicated commercial-account billing exists.

## Do Not

- Re-derive whether Manager should be treated as "admin" for location-scoping — it should not; use `User::scopedLocationId()` (DECISIONS.md #29, #42-46 document two rounds of this exact bug).
- Add a new list/index view without checking `scopedLocationId()` on it.
- Add a payment method, refund path, or financial mutation without wrapping it in a DB transaction with row locking, matching the existing `PaymentService`/`OrderService`/`InventoryService` pattern.
