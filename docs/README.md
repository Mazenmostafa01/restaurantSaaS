# aklaSaaS Technical Documentation

Welcome to the technical documentation for **aklaSaaS**, a multi-tenant restaurant software-as-a-service platform with voice and natural language AI ordering.

---

## Documentation Index

| Document | Target Audience | Summary |
|---|---|---|
| [`AGENTS.md`](../AGENTS.md) | AI Coding Agents | The primary, load-bearing context file containing strict architectural rules, tenancy guidelines, conventions, and verification steps. |
| [`docs/architecture.md`](./architecture.md) | Developers & Architects | Comprehensive system architecture: tenancy model, dual guard/frontend structure, RBAC, financial calculation, and caching. |
| [`docs/ai-ordering.md`](./ai-ordering.md) | Developers & Product Engineers | Complete reference for the AI Natural Language & Voice Order Parser (`laravel/ai`): agent prompt engineering, Egyptian Arabic & Franco support, El-Shilla bill splitting, security guards, API reference, and React UI components. |
| [`docs/ai-ordering-plan.md`](./ai-ordering-plan.md) | Technical Historians | Original Phase 1 implementation roadmap and step-by-step verification log. |

---

## Quick Technology Overview

- **Backend**: Laravel 13 running on PHP 8.4
- **Database**: MySQL with compound tenant indexes
- **Caching & Queues**: Redis
- **Real-Time WebSockets**: Laravel Reverb
- **AI Engine**: `laravel/ai` SDK with Google Gemini 3.5 Flash Lite / 2.5 Flash
- **Frontend (Admin)**: Blade + Tailwind CSS 4 + Alpine.js
- **Frontend (Customer)**: React 19 SPA + Tailwind CSS 4
