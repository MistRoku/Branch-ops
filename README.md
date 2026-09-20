# BranchOps Platform

A complete multi-branch retail operations platform built with Laravel 11.

## Features

- **POS Terminal**: Point-of-sale interface for processing sales
- **Admin Dashboard**: Real-time operations overview with KPIs
- **Inventory Management**: Stock control, transfers, and stock takes
- **Product Management**: Full CRUD for products with categories
- **Supplier Management**: Supplier records and purchase orders
- **User Management**: Role-based access control across branches
- **Reports Engine**: PDF and Excel report generation
- **Document Management**: File uploads with S3-compatible storage
- **Real-time Updates**: WebSocket-powered live dashboard
- **Audit Logging**: Complete audit trail for compliance
- **Global Search**: Meilisearch-powered full-text search

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Database | MySQL 8 |
| Real-time | Laravel Reverb (WebSocket) |
| Queue | Redis + Laravel Horizon |
| Cache | Redis |
| Search | Laravel Scout + Meilisearch |
| File Storage | Local + S3-compatible (MinIO) |

## Design System

The frontend follows strict enterprise design guidelines:

- **Font**: IBM Plex Sans only
- **Colors**: Flat enterprise palette (no pastels, no neon)
- **Borders**: Sharp edges (no rounded corners)
- **Shadows**: None (completely flat)
- **Animations**: No hover animations
- **Icons**: Simple SVG strokes (no emojis, no decorative elements)
- **Layout**: Horizontal scrollable KPI regions (no 3-card grids)

## Quick Start

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8
- Redis

### Installation

```bash
# Clone the repository
cd branchops

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start the development server
php artisan serve
```

### Docker Development

```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Access the application at http://localhost
```

## Project Structure

```
branchops/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/      # Admin panel controllers
│   │   │   └── Api/V1/     # API controllers
│   │   ├── Middleware/     # Custom middleware
│   │   └── Requests/       # Form requests
│   ├── Models/             # Eloquent models
│   ├── Policies/           # Authorization policies
│   ├── Services/           # Business logic services
│   ├── Events/             # Broadcast events
│   ├── Notifications/      # Notification classes
│   └── Jobs/               # Queue jobs
├── resources/
│   ├── css/                # Tailwind CSS
│   ├── js/                 # Alpine.js and Echo
│   └── views/
│       ├── components/     # Blade components
│       │   ├── ui/         # UI components
│       │   └── layouts/    # Layout templates
│       ├── admin/          # Admin views
│       ├── pos/            # POS views
│       └── auth/           # Auth views
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # API routes
└── docker/                 # Docker configuration
```

## License

MIT License
