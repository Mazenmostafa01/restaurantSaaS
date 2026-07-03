# AGENTS.md

> **Audience:** AI coding agents (Mavis, Claude Code, Cursor, Codex,
> Copilot, etc.) working in this repo. Humans should read
> [`docs/architecture.md`](./docs/architecture.md) instead — it has
> more narrative.

This file is the load-bearing context. If you (the agent) only read
one file in this repo, read this one.

---

## 1. What this project is (one paragraph)

aklaSaaS is a **multi-tenant Laravel 12 SaaS for restaurants**. One
installation serves many restaurants. Each restaurant has staff
(`admin` + `employee` roles via Spatie) and end customers. Staff use a
Blade-rendered admin panel at `/`. Customers use a React SPA served
from `/{restaurant:slug}/...`. Backend is Laravel 12 + MySQL + Reverb
(WebSockets) + Sanctum (auth) + Spatie (RBAC).

## 2. The non-negotiable rule: tenancy

**`Restaurant` is the tenant.** A `TenantContext` singleton in the
container holds the current `Restaurant` for the request lifetime.
Every model that holds tenant data uses the `BelongsToTenant` trait,
which:

- registers a `TenantScope` global scope that adds
  `WHERE restaurant_id = app(TenantContext::class)->id()` to every
  query, and
- auto-fills `restaurant_id` on `creating` if the caller didn't pass it.

The tenant is set by one of two middlewares (registered in
`bootstrap/app.php`):

- `SetTenantContext` — for admin/staff routes. Reads
  `User->restaurant_id` from the logged-in user.
- `SetCustomerTenantContext` — for customer API routes. Reads
  `{restaurant:slug}` from the route param. **Also enforces that the
  authenticated customer's `restaurant_id` matches the route's
  restaurant** (cross-tenant check).

### What this means for you (the agent)

- **Never write a query that bypasses `BelongsToTenant` without a
  comment justifying it.** If you need cross-tenant data, use
  `Model::withoutGlobalScopes()` and write a test that proves the
  bypass is correct.
- **Never manually set `restaurant_id` in a controller** — the trait
  does it. Only seeders and tests should set it explicitly.
- **Tenant scope is only active if a tenant is set.** In tinker or
  one-off scripts, the scope is a no-op. Don't be surprised.
- **Cross-tenant auth is enforced** at the `customer-tenant`
  middleware, not at the model. If you see a 401 on a customer
  endpoint, check the URL — it might be the wrong restaurant slug.

## 3. Two populations, two guards, two frontends

| | Admin staff | Customer |
| --- | --- | --- |
| Model | `App\Models\User` | `App\Models\Customer` |
| Guard | `web` | `customer` |
| Frontend | Blade | React SPA |
| Routes | `routes/web.php` (auth + tenant group) | `routes/web.php` catch-all + `routes/api.php` |
| Tenant source | `User.restaurant_id` | `{restaurant:slug}` route param |
| Tenant middleware | `tenant` | `customer-tenant` |
| Roles | Spatie `admin`, `employee` | none |
| Migrations | `users`, `restaurants`, `permission_tables` | `customers` (with composite unique per restaurant) |

**Never mix the two.** A `User` can never be a `Customer`; a `Customer`
can never log in via `web`. They share `Restaurant` only.

## 4. Where things live

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php             # admin web auth
│   │   ├── ItemController.php             # admin: items CRUD (admin-only for edit/delete)
│   │   ├── OrderController.php            # admin: orders CRUD (admin-only for edit/delete)
│   │   ├── Dashboard/DashBoardController.php  # admin: analytics (admin-only)
│   │   └── Api/Customer/                  # customer JSON API (5 controllers)
│   ├── Middleware/
│   │   ├── SetTenantContext.php           # admin tenant resolver
│   │   └── SetCustomerTenantContext.php   # customer tenant resolver (with cross-tenant check)
│   └── Requests/                          # FormRequest validation (scoped by tenant)
├── Models/
│   ├── Restaurant.php                     # the tenant
│   ├── User.php                           # admin staff (BelongsToTenant + HasRoles)
│   ├── Customer.php                       # end customer (BelongsToTenant + HasApiTokens)
│   ├── Item.php, Order.php, Attachment.php
│   └── Scopes/TenantScope.php             # global query filter
├── Services/TenantContext.php             # singleton: current Restaurant
├── Traits/
│   ├── BelongsToTenant.php                # registers scope + auto-stamps restaurant_id
│   └── Calculation.php                    # tax/subtotal math (14% flat tax)
└── Events/ItemUpdateEvent.php             # broadcast on item update (Reverb)

routes/
├── web.php                                # admin routes + customer SPA catch-all (catch-all is LAST)
├── api.php                                # customer JSON API
└── channels.php                           # Reverb broadcast auth

resources/
├── js/app.js                              # admin panel entry
├── js/customer-app/                       # customer React SPA (App.jsx, pages/, components/, context/)
└── views/customer/spa.blade.php           # Blade shell that mounts React with window.__RESTAURANT__

database/migrations/                       # see docs/architecture.md §6
```

## 5. Patterns to follow

### Adding a new tenant-scoped model

1. `php artisan make:model Foo -mfsc`
2. In the generated migration: add `restaurant_id` FK + composite index
   (copy the pattern from
   `2026_04_18_000002_add_restaurant_id_to_tenant_tables.php`).
3. In the model: `use BelongsToTenant;`
4. In controllers: use the existing `BelongsToTenant` controllers as
   reference — `ItemController.php` is the cleanest example.
5. In routes: nest under the `auth + tenant` middleware group.
6. Test with `RefreshDatabase`. Don't forget to set `TenantContext`
   in the test, or use `withoutGlobalScopes()` to verify scope
   behavior.

### Adding a customer API endpoint

1. Add the route in `routes/api.php` under
   `prefix('customer/{restaurant:slug}')->middleware('customer-tenant')`
   (or the authed group).
2. Add the controller in `app/Http/Controllers/Api/Customer/`. Tenant
   scope is automatic — write the query as you would normally.
3. In the React app: add the API call in
   `resources/js/customer-app/api/` and the page in `pages/`.

### FormRequest validation

Always scope uniqueness/exists rules by tenant:

```php
$tenantId = app(TenantContext::class)->id();
$rule = Rule::unique('items', 'name');
if ($tenantId !== null) {
    $rule->where('restaurant_id', $tenantId);
}
```

This is the convention in `AddItemRequest`, `UpdateItemRequest`,
`OrderCreateRequest`, `OrderUpdateRequest`.

### Money

- DB columns are `decimal` (e.g. `price`, `tax`, `net`).
- Output formats to 2 decimals: `number_format($x, 2)`.
- Server-side calculation only. The client sends items + quantities,
  never prices.

### Tax

- Flat 14%. `App\Traits\Calculation::tax($subTotal) = $subTotal * 0.14`.
- Used in `OrderController::store`, `OrderController::update`,
  `CustomerOrderController::store`. If you add a third order-creating
  path, use the trait.

## 6. Patterns to avoid

- **Don't put routes after the customer SPA catch-all in
  `routes/web.php`.** It's last on purpose.
- **Don't add new role checks as `abort_unless(auth()->user()->hasRole(...))`
  inline in controllers** — prefer the route's `->middleware('role:...')`
  declaration. (Existing inline checks are being phased out; the
  Spatie middleware aliases are already registered in
  `bootstrap/app.php`.)
- **Don't write raw `DB::` joins.** Use Eloquent + relationships.
- **Don't use `$casts` property** on models — use the `casts()` method.
- **Don't use Tailwind v3 utilities** (e.g. `bg-opacity-50`). v4 uses
  `bg-black/50` syntax. See `.github/copilot-instructions.md` for the
  full Tailwind 4 rules.
- **Don't bypass `TenantScope` without writing a test** that proves
  the bypass is intentional.
- **Don't put `restaurant_id` in form data** — the trait auto-fills
  it. If you find yourself setting it manually, something is wrong
  upstream (missing middleware, etc.).
- **Don't store money as floats in new code paths** — use the
  existing `decimal` columns and format on output.

## 7. Verification before you claim a change is done

```bash
# 1. Format
vendor/bin/pint --dirty

# 2. Tests for the affected area
php artisan test --filter=<relevant test or method>

# 3. Full test suite
php artisan test
```

If you added a new feature, also:

```bash
# 4. Build assets (only if you changed JS/CSS)
npm run build
```

## 8. Quick reference

| You want to... | Look at |
| --- | --- |
| Understand the tenant pattern | `app/Services/TenantContext.php` + `app/Models/Scopes/TenantScope.php` + `app/Traits/BelongsToTenant.php` |
| Add a tenant-scoped model | `app/Models/Item.php` (cleanest example) |
| Add a customer API endpoint | `app/Http/Controllers/Api/Customer/CustomerOrderController.php` |
| Add admin auth-gated routes | `routes/web.php` lines 13-40 |
| Add a new Spatie role | `docs/architecture.md` §4 |
| Change tax math | `app/Traits/Calculation.php` (single source) |
| Wire a new Reverb channel | `app/Events/ItemUpdateEvent.php` + `routes/channels.php` |
| Run the full docs | [`docs/README.md`](./docs/README.md) |

## 9. What's intentionally not in the repo

- **No user/restaurant seeders.** First-run setup is documented in
  [`docs/onboarding.md`](./docs/onboarding.md) §2.
- **No CI/CD pipeline.** See [`docs/deployment.md`](./docs/deployment.md) §8.
- **No subdomain routing.** The `restaurants.slug` column is reserved
  for it. Currently path-based: `/{restaurant:slug}/...`.
- **No payment integration.** Orders are placed without payment.
- **No i18n.** All UI is English.

If your task is to add one of these, design before coding.
