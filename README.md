# aklaSaaS 🍽️🤖

**aklaSaaS** is a multi-tenant Laravel 13 Software-as-a-Service (SaaS) platform built for restaurants. A single deployment powers multiple restaurant tenants with isolated staff portals, customer single-page applications (SPAs), real-time order broadcasting, and an integrated **AI Natural Language & Voice Smart Ordering System** powered by `laravel/ai` and Google Gemini.

---

## 🌟 Key Features

### 🏢 Multi-Tenancy Architecture
- **Strict Isolation**: `Restaurant` is the root tenant. The `BelongsToTenant` trait automatically scopes queries via `TenantScope` and stamps `restaurant_id` on model creation.
- **Cross-Tenant Protection**: Dynamic route middlewares (`SetTenantContext` for staff, `SetCustomerTenantContext` for customer API) strictly enforce tenant boundaries and block cross-tenant token access.

### 👥 Dual Population & Frontends
- **Admin & Staff Portal**: Server-rendered Blade interface at `/` with Spatie RBAC (`Admin` and `Employee` roles) for menu management, order taking, and dashboard analytics.
- **Customer React SPA**: Modern React 19 SPA mounted at `/{restaurant:slug}` with live cart state, category browsing, and order history.

### 🎙️ AI Natural Language & Voice Order Parser (`laravel/ai`)
- **Voice & Text Ordering**: Customers can dictate orders via spoken voice notes ($\le 45\text{s}$ / $3\text{MB}$) or type freeform text.
- **Egyptian Arabic & Franco Support**: Handles spoken Egyptian Arabic (اللهجة المصرية), grammatical dual forms (المثنى: e.g. "ساندوتشين" $\to$ quantity 2, "كريبين" $\to$ 2), colloquial food terms, and Franco-Arabic (Arabizi: e.g. "2 burger w 1 batates").
- **"El-Shilla" Group Bill Splitting**: Automatically attributes items to individual friends (e.g. "Ahmed: 2 burger, Mona: 1 crepe") and computes itemized individual subtotals.
- **Interactive Review Modal**: Editable dishes, note customizations, and candidate suggestions for unrecognized items ("Did you mean...?").
- **Zero-Trust Security & Server-Authoritative Pricing**: Model responses are strictly grounded on the active restaurant's cached menu digest. Hallucinated or cross-tenant IDs are demoted to unmatched items, and prices + 14% VAT are calculated server-side from database models.

### ⚡ Performance & Real-Time Broadcasting
- **Dual Redis Caching**: Menu payloads (`tenant:{id}:menu`) and AI prompt digests (`tenant:{id}:ai_menu_digest`) are cached in Redis with automatic invalidation parity on item updates.
- **Laravel Reverb**: Real-time WebSocket broadcasting for dish availability and menu synchronization.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend Framework** | [Laravel 13](https://laravel.com) running on PHP 8.4 |
| **AI SDK** | [`laravel/ai`](https://laravel.com/docs/13.x/ai-sdk) + [Google Gemini 3.5 Flash Lite / 2.5 Flash](https://ai.google.dev/) |
| **Database** | MySQL with compound tenant indexes |
| **Cache & Queue** | Redis |
| **WebSockets** | [Laravel Reverb](https://reverb.laravel.com/) |
| **Admin Frontend** | Laravel Blade + Tailwind CSS 4 + Alpine.js |
| **Customer Frontend** | React 19 SPA + React Router 7 + Lucide Icons |
| **Authentication & RBAC** | Laravel Sanctum (Customer tokens) + Spatie Permission (Staff roles) |

---

## 🚀 Quick Start & Local Setup

### Prerequisites
- PHP 8.4+ with `pdo_mysql`, `redis`, `mbstring`, `fileinfo` extensions
- Composer
- Node.js 20+ & npm
- MySQL & Redis
- [Laravel Herd](https://herd.laravel.com) (Recommended) or PHP CLI

### 1. Clone & Install Dependencies

```bash
git clone <repository_url> restaurantSaaS
cd restaurantSaaS

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### 2. Environment Configuration

Copy `.env.example` to `.env` and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database, Redis, and AI keys in `.env`:

```dotenv
APP_NAME="aklaSaaS"
APP_URL=http://restaurantsaas.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_saas
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Google Gemini API key for AI order parsing
GEMINI_API_KEY=your_gemini_api_key_here
AI_DEFAULT_PROVIDER=gemini
AI_ORDER_PARSER_MODEL=gemini-3.5-flash-lite
```

### 3. Run Migrations & Storage Link

```bash
php artisan migrate
php artisan storage:link
```

### 4. Build Frontend Assets & Run Dev Servers

```bash
# Build React & Tailwind assets
npm run build

# Or run during development
npm run dev
```

---

## 🤖 AI Smart Ordering Usage

1. **Browse as Customer**: Navigate to `http://restaurantsaas.test/{restaurant-slug}/menu`.
2. **Launch AI Modal**: Click the **✨ AI Smart Order** button in the header, menu banner, or cart.
3. **Voice or Text**:
   - **Voice**: Click the microphone icon to record your order (up to 45 seconds).
   - **Text**: Type naturally in Arabic, Franco, or English (e.g., `"عايز اتنين برجر واحد بدون بصل وواحد كريب، وأحمد عايز بيبسي"`).
4. **Review & Confirm**: Review matched items, edit quantities, inspect group split cards, and add directly to your cart.

---

## 🧪 Testing & Verification

Run the test suite using PHPUnit or Artisan:

```bash
# Run AI Parser feature & unit tests
php artisan test --compact tests/Feature/CustomerOrderParseTest.php
php artisan test --compact tests/Unit/NaturalLanguageOrderParserServiceTest.php

# Run Tenant Redis Caching tests
php artisan test --compact tests/Feature/TenantRedisCachingTest.php

# Run full test suite
php artisan test --compact

# Code style formatter (Laravel Pint)
vendor/bin/pint --dirty --format agent
```

---

## 📚 Technical Documentation

For in-depth architecture, API contracts, and implementation details:

- **[`AGENTS.md`](./AGENTS.md)**: Primary instructions and strict constraints for AI coding agents.
- **[`docs/architecture.md`](./docs/architecture.md)**: Comprehensive architectural guide (tenancy, guards, Spatie RBAC, database schema).
- **[`docs/ai-ordering.md`](./docs/ai-ordering.md)**: Deep technical dive into `laravel/ai`, prompt engineering, Egyptian Arabic nuances, and security guards.
- **[`docs/README.md`](./docs/README.md)**: Technical documentation index.
