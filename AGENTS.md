# AGENTS.md — BranchOps Platform

Multi-branch retail operations platform (Laravel 11, PHP 8.3+, SQLite/MySQL, Tailwind 3, Alpine.js, Vite).

## Run locally

- Backend (pick ONE host per session, cookies do not cross hosts): `php artisan serve` → `http://127.0.0.1:8000`, or Laragon Apache → `http://Branch-ops.test`.
- Frontend assets: `npm run dev` alongside the backend (HMR at `127.0.0.1:5173`, see `vite.config.js`), or `npm run build` for a manifest build. Never commit `public/hot`.
- Node lives at `C:\laragon\bin\nodejs\node-v22` on this machine; prepend to PATH before npm.
- DB: sqlite by default (`DB_CONNECTION=sqlite`). `php artisan migrate --force` to upgrade.
- Demo logins (password `password123`): `admin@branchops.test` (super_admin), `manager@branchops.test` (branch_manager, Johannesburg), `staff@branchops.test` (staff).

## Architecture notes

- Routes: `routes/web.php` (login, POS, legal), `routes/api.php` (loaded under `/api`; canonical POS paths are `/api/v1/*`, unprefixed `/api/*` aliases kept for older callers), `routes/admin.php` (mounted with `web,auth` prefix `admin` in `bootstrap/app.php`).
- Sanctum cookie auth for the POS requires `$middleware->statefulApi()` (already set) plus `SANCTUM_STATEFUL_DOMAINS` covering each dev host in `.env`.
- Roles are `super_admin | branch_manager | staff` with helpers `isSuperAdmin()/isBranchManager()/canSeeCostPrices()` on `User`. Use the `role:` middleware alias or controller checks; never compare to `admin`/`manager`.
- Money columns are `total_amount/subtotal/tax_amount/discount_amount` (there is no `total` column; `Sale::getTotalAttribute` aliases it). Payments are `cash|card|split` with an optional `payments[]` breakdown; tips, coupons, delivery fee, and customer loyalty live on `sales`.
- Stock moves only through `InventoryService::adjustStock()` (row-locked, clamps at zero, records `stock_movements`, fires `StockLowAlert` after commit). Exact quantities are back-office only; the POS shows availability status.
- PDF via `dompdf/dompdf` (`Dompdf\Dompdf` directly), CSV via native `fputcsv`. Sanctum token migrations are published (tests use `:memory:` sqlite).

## Frontend constraints (must keep)

- Flat enterprise style: off-white surfaces (`bg-brand-50/100`, never pure white), IBM Plex Sans only, sharp corners (`rounded-none`), no shadows, no gradients, no hover/transition animations (`transition-none` globally).
- Lucide-style inline SVG icons only, no emojis. Skeleton loaders for async regions, `[x-cloak]` for Alpine-gated markup.
- TOS (`/terms`) and Privacy (`/privacy`) links in every layout footer.

## Verification before finishing

- `npm run build` must pass; `php artisan test` must be green (covers `SalesApiTest` checkout/refund/void/scope, `SecurityTest`, lockout, inventory).
- Exercise auth-gated flows with the session cookie on a single host when touching login, POS, or `auth:sanctum` routes.
