# BranchOps Platform

Multi-branch retail operations platform — POS terminal, inventory, transfers, purchasing, and role-based admin. Built with Laravel, Blade, Tailwind CSS, and Alpine.js.

## Screenshots

> Capture these in 2 minutes against seeded demo data (`php artisan migrate:fresh --seed`, log in as `admin@branchops.test` / `password123`):

| POS terminal (`/pos`) | Dashboard (`/admin/dashboard`) | Inventory (`/admin/inventory`) |
|---|---|---|
| `docs/screenshots/pos.png` | `docs/screenshots/dashboard.png` | `docs/screenshots/inventory.png` |

![POS terminal](docs/screenshots/pos.png)
![Admin dashboard](docs/screenshots/dashboard.png)
![Inventory](docs/screenshots/inventory.png)

## Features

- **POS terminal** — product grid, cart, checkout, sale recording with atomic stock reduction
- **Admin dashboard** — KPIs with day-over-day deltas, 14-day revenue chart, live activity feed, branch filter
- **Inventory** — stock levels, low-stock alerts, manual adjustments (row-locked), movement history, valuation
- **Transfers** — inter-branch requests with approve / receive / reject workflow
- **Stock takes** — branch counts with automatic variance posting
- **Suppliers & purchase orders** — supplier CRUD, PO draft → sent → received / cancelled, goods receipt posts stock
- **Users & access control** — super_admin / branch_manager / staff, branch scoping, cost-price visibility gates, account lockout, inactive-user blocking
- **Global search** — permission-scoped, relevance-ranked search across products, suppliers, branches, orders, users, reports
- **Reports & documents** — CSV exports, document upload/download with validation
- **Audit trail** — immutable audit logs with admin viewer
- **Notifications** — in-app center with read / read-all

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3+ (verified on 8.5) |
| Frontend | Blade, Tailwind CSS 3, Alpine.js, Chart.js |
| Auth | Session + Sanctum tokens, hierarchical roles |
| Database | SQLite (local) / MySQL 8 (Docker & prod) |
| Realtime | Laravel Reverb / Echo (broadcast events ship; driver optional locally) |
| Quality | Pint, PHPStan (level 5), PHPUnit, GitHub Actions CI |
| Deploy | Dockerfile + docker-compose (app, nginx, MySQL, Redis) |

## Quick start

Prerequisites: PHP 8.3+, Composer, Node.js 20+.

```sh
# Linux / macOS
./setup.sh && php artisan serve

# Windows (PowerShell)
setup.bat
php artisan serve
```

Then open http://localhost:8000 and log in:

| Role | Email | Password |
|---|---|---|
| Super admin | admin@branchops.test | password123 |
| Branch manager | manager@branchops.test | password123 |
| Staff | staff@branchops.test | password123 |

What `setup.*` does: installs PHP + JS dependencies, creates `.env` + app key, runs migrations with demo seeders (`DemoDataSeeder`: 2 branches, 3 users, 2 suppliers, 8 products, stocked levels including a low-stock line), and builds frontend assets.

Manual equivalent:

```sh
composer install          # Windows: add --ignore-platform-reqs (Horizon needs pcntl/posix)
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

### Docker

```sh
docker compose up -d --build
docker compose exec app php artisan migrate --seed
# App: http://localhost:8080
```

## Project structure

```text
app/
├── Http/Controllers/
│   ├── Admin/      # Dashboard, Products, Inventory, Transfers, StockTakes,
│   │               #   PurchaseOrders, Suppliers, Users, Reports, Documents,
│   │               #   Notifications, AuditLogs, Settings
│   ├── Api/V1/     # Dashboard, Products, Inventory, Sales, Suppliers, Search
│   └── Auth/       # Login with lockout + inactive-account handling
├── Services/       # Inventory (row-locked), Transfer, StockTake, PurchaseOrder, Cash, Return
├── Policies/       # Sale, Document, User
├── Events/         # SaleRecorded, StockLowAlert (broadcast)
└── Http/Middleware/# AddSecurityHeaders, EnsureUserIsActive
resources/views/
├── admin/          # One folder per module above
├── pos/            # Terminal
└── components/     # layouts + ui kit (Tailwind, flat, accessible)
routes/
├── web.php         # Auth, POS, legal pages
├── admin.php       # Full admin CRUD (loaded under /admin by bootstrap/app.php)
└── api.php         # Sanctum-protected /api/v1
```

## Security model

Three roles with branch scoping: non-admins only see their branch; cost prices hidden from staff at API and view level; inactive accounts blocked on web and API; 5-strike login lockout (30 min) plus route throttling; security headers middleware; validated file uploads; immutable audit logs.

## Testing & code style

```sh
php artisan test        # Feature (auth, permissions) + unit
vendor/bin/pint --test  # Laravel code style (CI enforces)
vendor/bin/phpstan      # Level 5 static analysis
```

## License

MIT — see [LICENSE](LICENSE).
