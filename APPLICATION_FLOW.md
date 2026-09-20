# Application Flow Documentation

## BranchOps Platform - User Journey

### 1. Entry Point (Unauthenticated)
```
URL: /login
View: resources/views/auth/login.blade.php
Layout: components/layouts/guest.blade.php
```

**Features:**
- Clean login form with email/password
- BranchOps logo (blue square with "B")
- Links to Terms of Service and Privacy Policy
- Error display for failed authentication
- CSRF protection on form submission

**After successful login:**
- Session created and regenerated (security)
- Redirect to POS Terminal (`/pos`)

---

### 2. POS Terminal (Default Landing Page)
```
URL: /pos
View: resources/views/pos/terminal.blade.php
Layout: components/layouts/pos.blade.php
```

**Layout Structure:**
- **Header Bar** (top, full width):
  - Left: BranchOps branding + branch name
  - Right: 
    - "Back Office" button (for admins/managers only)
    - User name and role
    - Logout button
  
- **Main Content Area**:
  - Left Panel (75% width): Product grid with search/filter
  - Right Panel (25% width): Shopping cart
  
- **Connection Status** (bottom-right corner):
  - Green dot + "Connected" when WebSocket active
  - Red dot + "Disconnected" when offline
  - Auto-reconnect logic via Alpine.js

**POS Features:**
- Product search with debounced input
- Category filter dropdown
- Product cards with skeleton loaders
- Add to cart on click (prevents out-of-stock items)
- Cart quantity controls (+/- buttons)
- Real-time subtotal, tax (10%), total calculation
- Checkout modal with success confirmation
- Clear cart after completed sale

**API Integration:**
```javascript
GET  /api/v1/products      // Load product catalog
POST /api/v1/sales         // Process checkout
```

---

### 3. Back Office Dashboard
```
URL: /admin/dashboard
View: resources/views/admin/dashboard.blade.php
Layout: components/layouts/admin.blade.php
```

**Access:** Admins and Managers only (via role check in layout)

**Layout Structure:**
- **Sidebar** (left, collapsible):
  - Dashboard (active)
  - Products
  - Inventory
  - Transfers
  - Stock Takes
  - Suppliers
  - Purchase Orders
  - Reports
  - Users (Admin only)
  - Audit Logs (Admin only)
  - Settings
  
- **Top Bar**:
  - Global search
  - Notification bell
  - User menu
  
- **Main Content**:
  - KPI cards (horizontal scrollable)
  - Revenue chart
  - Recent activity feed
  - Low stock alerts

---

## Route Configuration

### Web Routes (`routes/web.php`)

```php
// Guest routes (not logged in)
GET  /login           -> Login form
POST /login           -> Process login

// Authenticated routes
POST /logout          -> Logout
GET  /                -> Redirect to /pos
GET  /pos             -> POS Terminal

// Public legal pages
GET  /terms           -> Terms of Service
GET  /privacy         -> Privacy Policy
```

### Admin Routes (`routes/admin.php`)

```php
GET  /admin/dashboard              -> Dashboard view
GET  /admin/products               -> Product list
GET  /admin/products/create        -> Create product form
GET  /admin/products/{id}          -> Product detail
PUT  /admin/products/{id}          -> Update product
DELETE /admin/products/{id}        -> Delete product
GET  /admin/inventory              -> Stock levels
POST /admin/inventory/adjust       -> Adjust stock
GET  /admin/inventory/movements    -> Movement history
... (additional admin routes)
```

### API Routes (`routes/api.php`)

```php
// Products
GET    /api/v1/products            -> List products
POST   /api/v1/products            -> Create product
GET    /api/v1/products/{id}       -> Get product
PUT    /api/v1/products/{id}       -> Update product
DELETE /api/v1/products/{id}       -> Delete product

// Inventory
GET    /api/v1/inventory           -> Stock levels
POST   /api/v1/inventory/adjust    -> Adjust stock
GET    /api/v1/inventory/movements -> Movement history
GET    /api/v1/reports/valuation   -> Inventory valuation

// Sales
POST   /api/v1/sales               -> Create sale (checkout)
GET    /api/v1/sales               -> List sales
GET    /api/v1/sales/{id}          -> Sale detail

// Suppliers
GET    /api/v1/suppliers           -> List suppliers
POST   /api/v1/suppliers           -> Create supplier
... (CRUD operations)

// Dashboard
GET    /api/v1/dashboard/kpis      -> KPI data
GET    /api/v1/dashboard/chart     -> Chart data
GET    /api/v1/dashboard/activity  -> Activity feed

// Search
GET    /api/v1/search              -> Global search
```

---

## Authentication Flow

```
┌─────────────┐
│   /login    │
│   (Guest)   │
└──────┬──────┘
       │ POST credentials
       ▼
┌─────────────────┐
│  Validate Input │
│  Check Database │
└──────┬──────────┘
       │ Success          │ Failed
       ▼                  ▼
┌─────────────┐    ┌──────────────┐
│Regenerate   │    │ Redirect back│
│  Session    │    │ with error   │
└──────┬──────┘    └──────────────┘
       │
       ▼
┌─────────────┐
│Redirect to  │
│   /pos      │
└─────────────┘
```

---

## Design Principles Applied

### Visual Style
✅ Flat design (no shadows, gradients, glass effects)  
✅ Sharp edges (border-radius: 0)  
✅ IBM Plex Sans font only  
✅ No emojis in UI  
✅ No hover animations  
✅ Skeleton loaders for async content  
✅ Horizontal scroll for KPI regions  

### Color Palette
- Primary: Blue (#2563eb)
- Text: Slate (#0f172a)
- Background: Gray (#f8fafc)
- Borders: Gray (#e5e7eb)
- Success: Green (#16a34a)
- Warning: Yellow (#ca8a04)
- Danger: Red (#dc2626)

### Typography
- Font: IBM Plex Sans (400, 500, 600, 700)
- No Inter, Geist, or Space Grotesk
- Consistent sizing scale (text-xs to text-2xl)

---

## Security Measures

1. **CSRF Protection**: All state-changing forms include `@csrf`
2. **Session Regeneration**: On login to prevent fixation attacks
3. **Input Validation**: Email format, required fields
4. **Role-Based Access**: Admin/Manager checks in layouts
5. **SQL Injection Prevention**: Eloquent parameterized queries
6. **XSS Prevention**: Blade auto-escaping
7. **Secure Headers**: Configured in middleware

---

## Next Steps for Full Implementation

### Remaining Backend Features
- [ ] Transfer workflow (create, approve, receive)
- [ ] Stock take workflow
- [ ] Purchase order workflow (create, send, receive)
- [ ] Supplier management CRUD
- [ ] User management CRUD
- [ ] Report generation (PDF/Excel)
- [ ] Document upload/preview/download
- [ ] Notification system
- [ ] Audit log viewer
- [ ] Settings pages

### Remaining Frontend Features
- [ ] Product create/edit/delete forms
- [ ] Inventory adjustment modals
- [ ] Transfer creation/approval UI
- [ ] Stock take interface
- [ ] Supplier CRUD pages
- [ ] Purchase order workflow UI
- [ ] User management interface
- [ ] Reports library with filters
- [ ] Document manager
- [ ] Notification center
- [ ] Audit log viewer
- [ ] Settings tabs

### Infrastructure
- [ ] Docker Compose setup
- [ ] GitHub Actions CI/CD
- [ ] Meilisearch integration
- [ ] Laravel Reverb WebSocket server
- [ ] Redis queue configuration
- [ ] MinIO/S3 storage setup
- [ ] Test suite (Feature + Unit)

---

## Testing Checklist

### Manual Testing
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (error display)
- [ ] Navigate from POS to Back Office
- [ ] Add products to cart
- [ ] Complete checkout
- [ ] View success modal
- [ ] Start new sale
- [ ] Logout and re-login
- [ ] Access control (Staff vs Manager vs Admin)

### Automated Testing (Future)
```bash
php artisan test --filter LoginTest
php artisan test --filter POSTest
php artisan test --filter CheckoutTest
php artisan test --filter PermissionTest
```

---

## File Structure Summary

```
branchops/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── InventoryController.php
│   │   │   └── Api/V1/
│   │   │       ├── ProductController.php
│   │   │       ├── InventoryController.php
│   │   │       ├── SalesController.php
│   │   │       ├── SupplierController.php
│   │   │       ├── DashboardController.php
│   │   │       └── SearchController.php
│   │   └── Middleware/
│   │       └── (security middleware - TODO)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Sale.php
│   │   └── (other models)
│   └── Services/
│       └── InventoryService.php
├── resources/
│   ├── css/
│   │   └── app.css (design system)
│   ├── js/
│   │   └── app.js (Alpine.js + Echo)
│   └── views/
│       ├── auth/
│       │   ├── login.blade.php
│       │   ├── terms.blade.php
│       │   └── privacy.blade.php
│       ├── components/
│       │   ├── layouts/
│       │   │   ├── guest.blade.php
│       │   │   ├── pos.blade.php
│       │   │   └── admin.blade.php
│       │   └── ui/
│       │       ├── button.blade.php
│       │       ├── input.blade.php
│       │       ├── card.blade.php
│       │       └── (other components)
│       ├── pos/
│       │   └── terminal.blade.php
│       └── admin/
│           ├── dashboard.blade.php
│           ├── products/
│           └── inventory/
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── api.php
└── public/
    └── build/ (Vite assets)
```

---

**Document Version:** 1.0  
**Last Updated:** Current Session  
**Status:** Foundation Complete, Feature Development In Progress
