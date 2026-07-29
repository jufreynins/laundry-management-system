# Deploying to Hostinger (laundry-management-system.jufreyninobayogportfolio.com)

This is a step-by-step guide for deploying via FTP/SFTP. No commands here have been run against the live server — this is a checklist for you to follow.

A ready-to-use `.env.production` has been created at the project root (NOT committed to git — it's in `.gitignore`) with your live database credentials already filled in, a freshly generated unique `APP_KEY`, and a freshly generated webhook secret. **Do not reuse your local dev `.env`'s `APP_KEY` in production — a production app must have its own.**

## 1. Before uploading: build locally

Shared hosting won't run `npm run build` for you. Build assets on your machine first:

```bash
npm run build
```

This produces `public/build/` — make sure that folder is included in what you upload (it's normally gitignored for the repo, but it must exist on the server).

## 2. Document root — the most common Hostinger Laravel mistake

Laravel's entry point is `public/index.php`, **not** the project root. If Hostinger points your subdomain's document root at the project root instead of `project/public`, visitors will see a directory listing or a 500 error, and (worse) your `.env` file, `app/`, `storage/`, etc. become web-accessible — a serious security problem.

In hPanel:
1. Go to **Websites → Subdomains** (or **Domains**), find `laundry-management-system.jufreyninobayogportfolio.com`.
2. Set its **document root** to point at the `public` folder inside wherever you upload the app — e.g. if you upload the whole project to `domains/jufreyninobayogportfolio.com/laundry-management-system/`, set the document root to `domains/jufreyninobayogportfolio.com/laundry-management-system/public`.

If Hostinger's interface doesn't let you set a custom document root for that subdomain, the fallback is: upload the project one level above `public_html`/the subdomain folder, then upload only the *contents* of `public/` into the subdomain's web root, and edit the uploaded `index.php` there to point `require`/`__DIR__` references up to the real `../laundry-management-system/vendor/autoload.php` and `../laundry-management-system/bootstrap/app.php`. This is fiddlier — try the document-root approach first.

## 3. What to upload via FTP/SFTP

Upload the **entire project**, including `vendor/` (Hostinger shared hosting typically can't run `composer install`, so `vendor/` must be uploaded as-is from your machine) and the `public/build/` folder you just built. Do **not** upload:
- `.git/`
- `node_modules/`
- your local `.env` (upload `.env.production` instead — see next step)
- `storage/logs/*.log` (leave the folder, skip the log files)

## 4. Set up `.env` on the server

Upload `.env.production` to the server as `.env` (rename it during/after upload — the server needs a file literally named `.env` in the project root, next to `artisan`).

Double check before going live:
- `DB_HOST=127.0.0.1` — this assumes Hostinger's MySQL is on the same server as your PHP (true for the vast majority of shared-hosting setups). If hPanel's database page shows a different host, update `DB_HOST` to match.
- `MAIL_MAILER=log` — **order status emails will currently only write to a log file, not actually send**, until you configure a real mail provider (Hostinger's own SMTP, or Mailgun/Postmark/SES). Update `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` when ready. See `app/Notifications/OrderStatusUpdated.php`.
- SMS notifications are still on the stub/logging provider (`LogSmsProvider`) — no real SMS vendor is integrated yet (see `docs/DECISIONS.md` #35). Customer SMS preferences can be turned on in the UI but nothing will actually be sent until a real provider is wired up.
- Online card payments are still on `StubPaymentProvider` — **do not advertise online card payment to real customers yet**; it simulates success/failure locally and does not process real charges (see `docs/DECISIONS.md` #38).

## 5. File permissions

Via FTP client or hPanel File Manager, ensure these are writable by the web server:
```
storage/
storage/app/
storage/app/private/
storage/framework/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/
```
755 is usually sufficient on Hostinger; only go to 775 if you get permission-denied errors in `storage/logs/laravel.log`.

## 6. Run migrations

The empty (1 MB) database needs the schema created. Two options depending on what your Hostinger plan includes:

**If you have SSH access** (check hPanel → Advanced → SSH Access):
```bash
ssh your-username@your-server
cd path/to/laundry-management-system
php artisan migrate --force
```

**If you don't have SSH**, use hPanel's **Cron Jobs** feature to run it once:
1. Create a cron job with command: `php /full/server/path/to/laundry-management-system/artisan migrate --force`
2. Set it to run once (e.g. schedule it a minute from now), let it fire, then **delete the cron job** immediately after — don't leave a migrate command running on a schedule.
3. Check `storage/logs/laravel.log` afterward to confirm it ran without errors.

Never run `migrate:fresh` or `migrate:rollback` against this database once it has real data — those are destructive.

## 7. Create your first Owner account

There's no public registration page (by design — see `CLAUDE.md`). After migrating, create the first Owner account directly via `php artisan tinker` (if you have SSH) or a one-time seeder you delete afterward:

```php
\App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'you@example.com',
    'password' => bcrypt('a-long-unique-password-here'),
    'role' => \App\Enums\UserRole::OWNER,
    'active' => true,
]);
```

Then create your first `Location` and `BusinessSettings` (tax rate, hours, etc.) from the app UI once logged in.

## 8. Verify

- Visit `https://laundry-management-system.jufreyninobayogportfolio.com/login` — confirm it loads over HTTPS with no mixed-content warnings.
- Log in as the Owner account you created.
- Confirm the dashboard loads.
- Create a test Location, Customer, Service, and Order end-to-end.
- Check that `storage/logs/laravel.log` has no errors from the above.
- Confirm `.env` is NOT publicly reachable: try visiting `https://laundry-management-system.jufreyninobayogportfolio.com/.env` in a browser — it must return a 404, not the file contents. If it returns the file, your document root is misconfigured (see step 2) — fix this immediately before doing anything else.

## 9. After going live

- Rotate `STUB_PAYMENT_WEBHOOK_SECRET` again if you ever suspect `.env` was exposed.
- Set up the backup schedule described in `docs/BACKUP_RESTORE.md` — a fresh production database has nothing to lose yet, but that changes fast once real orders start coming in.
