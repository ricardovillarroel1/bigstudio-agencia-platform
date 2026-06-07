# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this application does

Multi-tenant SaaS platform that integrates Shopify stores with Lioren (Chilean logistics/ERP system). It handles bidirectional product/inventory synchronization, subscription billing, Chilean DTE digital invoicing, and payment processing via Flow.cl. There is also an Agency module for managing client service subscriptions and an internal Finance module for accounting.

## Commands

```bash
# Development
php artisan serve          # Local dev server
npm run dev                # Vite dev server (Tailwind + Alpine.js)
npm run build              # Build frontend assets

# Database
php artisan migrate
php artisan tinker

# Testing
php vendor/bin/phpunit                        # Full test suite
php vendor/bin/phpunit --filter TestName      # Single test

# Code style
./vendor/bin/pint                             # Format PHP (Laravel Pint)

# Queue / scheduled tasks (local)
php artisan schedule:run                      # Run scheduled commands once
php artisan queue:work                        # Process queued jobs
```

## Architecture

### Roles and access control

Uses **Spatie Laravel Permission** with two primary roles: `admin` and `cliente`. Module-level permissions follow the pattern `modulo.accion` (e.g., `finanzas.ver`, `config.editar`). The `CheckRole` and `CheckModulePermission` middleware enforce these on route groups. See `SPATIE_PERMISSION.md` for details.

### Route structure (`routes/web.php`)

- **Public**: login, Shopify GDPR webhooks, Flow payment callbacks
- **Admin** (`auth + role:admin`): integrations, product/inventory sync, billing, client management, settings, analytics
- **Cliente** (`auth + role:cliente`): inventory, subscriptions, invoices, chat
- **Agency module**: full CRUD for client services, charges, subscriptions, quotes
- **Finance module**: income/expenses, IVA reconciliation, bank reconciliation

### Core services (`app/Services/`)

| Service | Purpose |
|---|---|
| `InventorySyncService` | Shopify ↔ Lioren inventory sync; handles multi-location warehouse mapping |
| `ProductSyncService` | Bidirectional product catalog sync (create/update/delete) |
| `WebhookSyncService` | Processes incoming Shopify webhooks (orders, products, inventory) |
| `IntegracionMulticlienteService` | Multi-client integration orchestration |
| `FlowService` | Flow.cl payment gateway (HMAC-SHA256 signed requests) |
| `FacturaServicioEmitter` | Emits Chilean DTE digital invoices for service billing |

### Shopify ↔ Lioren sync flow

1. **Lioren → Shopify** (`sync:lioren-to-shopify`, every 10 min): fetches stock from Lioren API, maps bodegas to Shopify locations via `LocationBodegaMapping`, updates inventory levels.
2. **Shopify → Lioren** (via webhooks): Shopify sends order/inventory webhooks to `/webhooks/*`; `WebhookSyncService` processes them and pushes updates to Lioren.
3. **Queue** (`sync:process-queue`, every 5 min): flushes pending sync items from `sync_queues` table.
4. SKU-level product mapping is stored in `ProductMapping`; warehouse mapping in `WarehouseMapping` and `LocationBodegaMapping`.

See `INTEGRACION_SHOPIFY_LIOREN.md` for the full architecture doc.

### Payment flow (Flow.cl)

`FlowService` creates payment orders and handles redirect + webhook confirmation. Requests are signed with HMAC-SHA256 using `FLOW_SECRET_KEY`. Set `FLOW_SANDBOX=true` in `.env` for testing. See `FLOW_INTEGRATION.md`.

### Chilean invoicing (DTE)

`FacturaServicioEmitter` generates electronic invoices (`FacturaServicio`, `FacturaEmitida`) and credit notes (`NotaCredito`). UF (Unidad de Fomento) conversion rates are fetched daily from Mindicador.cl and cached for 6 hours (`uf:update` command).

### Scheduled commands (`app/Console/Kernel.php`)

| Command | Schedule |
|---|---|
| `sync:process-queue` | every 5 min |
| `sync:lioren-to-shopify` | every 10 min |
| `dte:retry-failed` | every 15 min |
| `sync:detect-locations` | every 6 hours |
| `uf:update` | daily 8 AM |
| `suscripciones:notificar-vencimiento` | daily 9 AM |
| `facturacion:generar-anticipada` | daily 9:30 AM |
| `billing:process-cycles` | daily 1 AM |
| `suscripciones:verificar-vencimientos` | daily 12:05 AM |
| `agencia:cobros-automaticos` | daily 10 AM |
| `chats:close-inactive` | daily 2 AM |

### Key `.env` variables

```
FLOW_API_KEY, FLOW_SECRET_KEY, FLOW_SANDBOX
SHOPIFY_API_KEY, SHOPIFY_API_SECRET
LIOREN_API_URL, LIOREN_API_TOKEN
```

### Frontend

Blade templates with **Alpine.js** for interactivity and **Tailwind CSS** for styling. Assets built with Vite. No SPA framework — server-side rendering with occasional AJAX calls to web routes.

## Documentation files

The repo includes detailed markdown docs for specific subsystems:
- `INTEGRACION_SHOPIFY_LIOREN.md` — sync architecture
- `FLOW_INTEGRATION.md` — payment gateway
- `DOCUMENTACION_WEBHOOKS.md` — webhook patterns
- `WEBHOOKS_GDPR_SHOPIFY.md` — GDPR compliance
- `SPATIE_PERMISSION.md` — authorization system
- `IMPLEMENTACION_MULTICLIENTE.md` — multi-tenant support
- `INSTRUCCIONES_SUSCRIPCIONES.md` — subscription billing
- `DOCUMENTACION_NOTAS_CREDITO.md` — credit notes
- `DEPLOY_SHOPIFY_APP.md` — deployment
