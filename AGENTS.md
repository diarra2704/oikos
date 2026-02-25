# AGENTS.md

## Cursor Cloud specific instructions

### Overview

**Oikos** is a church discipleship management PWA (Progressive Web App) for ICC Yaounde. It manages church members organized into Familles de Disciples (FD) > Cellules > Membres, with role-based access (Admin, Superviseur, Leader de Cellule, Faiseur de Disciples).

- **Backend**: PHP 8.3 + Laravel 12 + Inertia.js
- **Frontend**: Vue 3 + TypeScript + Vite + Tailwind CSS
- **Database**: SQLite (self-contained, no external DB server needed)
- **Queue/Cache/Session**: All use the `database` driver (no Redis needed)

### Key commands

All standard commands are in `composer.json` scripts section:

| Task | Command |
|------|---------|
| Full setup | `composer setup` |
| Dev servers (all 4 concurrently) | `composer dev` |
| Laravel server only | `php artisan serve` |
| Vite HMR only | `npm run dev` |
| Queue worker | `php artisan queue:listen` |
| Tests | `composer test` |
| Lint check | `./vendor/bin/pint --test` |
| Lint fix | `./vendor/bin/pint` |
| Frontend build | `npm run build` |

### Non-obvious caveats

- **PHP 8.3 required**: The `composer.lock` pins `maennchen/zipstream-php` 3.2.1 which requires `php-64bit ^8.3`. PHP 8.2 will fail `composer install`.
- **SQLite compatibility**: The database defaults to SQLite. Any raw SQL must avoid MySQL-specific functions (e.g. use `strftime()` instead of `DAY()`/`MONTH()`/`YEAR()`).
- **Vite manifest required for tests**: Many feature tests fail with `ViteManifestNotFoundException` unless `npm run build` has been run first. Run `npm run build` before `composer test`.
- **Pre-existing test failures**: The Breeze-scaffolded tests (`AuthenticationTest`, `ProfileTest`, `RegistrationTest`, `PasswordUpdateTest`, `PasswordResetTest`, `PasswordConfirmationTest`, `EmailVerificationTest`, `ExampleTest`) use a default `UserFactory` with a `name` column, but the User model was customized to use `nom`/`prenom`. These are pre-existing failures, not regressions.
- **Demo credentials**: `admin@oikos.local` / `password` (Admin role). Other test accounts follow the pattern `superviseur.{fd_id}@oikos.local`, `leader.{fd_id}.{c}@oikos.local`, `faiseur.{fd_id}.{c}.{f}@oikos.local`, all with password `password`.
- **No external services required**: SMS (Twilio) is disabled by default, mail uses `log` driver, no Docker/Redis needed.
- **.env setup**: Copy `.env.example` to `.env`, then `php artisan key:generate`. The SQLite database file is at `database/database.sqlite`.
- **Database seeding**: Run `php artisan db:seed` to populate demo data (6 FDs, supervisors, leaders, faiseurs, and 150 members).
