# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Internal services portal for BPS (Badan Pusat Statistik), built on Laravel 13 / PHP 8.3+. The root `/` is a portal landing page; the main app, **MLTI** (IT device inventory + damage-report ticketing), lives under the `/mlti` prefix alongside "general services" (SIGAP complaints, Zoom and meeting-room booking). UI text, flash messages and many column names are in Indonesian — keep new user-facing strings in Indonesian.

## Commands

```bash
composer setup                 # install deps, copy .env, key:generate, migrate, npm install + build
composer dev                   # run the dev stack (php artisan dev)
php artisan migrate:fresh --seed   # rebuild DB with seed data (see Seeding below)

composer test                  # config:clear + php artisan test
php artisan test --filter=ReportTest                       # one test class
php artisan test --filter=test_user_can_login_using_direct_nip_lama   # one test method
vendor/bin/pint                # format PHP (Laravel Pint)
npm run build / npm run dev    # Vite (Tailwind 4) — only for resources/css|js/app.*
```

Tests use in-memory SQLite (`phpunit.xml`) with `RefreshDatabase`; each test seeds its own reference rows (Team, Room, Type, Source, StatusBmn, Condition) because the FKs require them.

## Architecture

**Auth — two login paths, both ending in `Auth::login()` on a local `users` row (no self-registration):**
- `Auth/QrLoginController`: `POST /login/verify` takes a scanned `qr_string`, looks it up in `qr_pusats` (→ `id_user`), falling back to treating a numeric string as `nip_lama`. Returns JSON.
- `Auth/BpsSsoController`: Keycloak OIDC (BPS SSO). Config lives in `config/services.php` → `bps_sso`, which accepts both `BPS_SSO_*` env names and the legacy names `KEYCLOAK_URL`/`REALM`/`CLIENT_ID`/`CLIENT_SECRET`/`REDIRECT_URI` used by the reference implementation in `examples/sso/`. The profile is matched to a local user via configurable claim lists (`BPS_SSO_NIP_CLAIMS` → `nip_baru`/`nip_lama`, then `BPS_SSO_EMAIL_CLAIMS` → `email`).

**Roles** are integer flags on `users`, enforced by middleware aliases registered in `bootstrap/app.php`:
- `jarkom` (`is_jarkom == 1`) → `/mlti/admin/*`: report handling/export, user & device CRUD, notifications, Zoom booking handling.
- `umum` (`is_jarkom` or `is_umum`) → `/mlti/umum/*`: SIGAP complaints and room bookings.

**Non-standard keys — important when writing queries/relations:**
- `User` primary key is `nip_lama` (int, non-incrementing). All user FKs (`reported_by`, `handled_by`, `devices.id_user`) point at `nip_lama`.
- `Device` primary key is a string `id` (non-incrementing).
- `devices.id_user` is overloaded: it holds either a user's `nip_lama` (personally assigned) **or** a `rooms.id` (device belongs to a room). Room ids are small integers, NIPs are 9 digits. `id_user = 15` is hard-coded as the "Gudang" (storage) room in `DashboardController` (`moveToGudang`, `swap`). `Device::user()` and `Device::room()` both join on `id_user`.
- `Report` auto-generates a unique `ticket_id` (`TCK-XXXXXX`) in its `creating` hook. `device_id` is nullable (network/room reports use `id_ruang` instead). Status values: `menunggu`, `diproses`, … (`Device::activeReport()` treats the first two as open).
- New reports notify all jarkom users via `Notifications\NewDeviceReport` (database channel + mail).

**Seeding is driven by Excel:** `DatabaseSeeder` first shells out to `database/seeders/convert_excel_to_json.py` (needs Python 3 + `openpyxl`; tries `py -3`/`python` on Windows, `python3`/`python` elsewhere) to regenerate `database/seeders/json/*.json` from `database/seeders/excel/*.xlsx`, then upserts from the JSON. Edit the `.xlsx` files, not the JSON — the JSON is overwritten on every seed. Seeded users get password `password` and synthetic `@example.com` emails.

**Frontend:** Blade views using the NiceAdmin Bootstrap template, loaded as static files from `public/assets/` via `asset()` in `resources/views/layouts/app.blade.php` (not through Vite). `backup/` is the pristine template copy for reference. `public/qrpusat/` and `public/qrjambi/` hold per-user QR images named by NIP.
