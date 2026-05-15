# Kiosco Credencialización

CodeIgniter 4.7 / PHP 8.2 application for credential issuance workflow.

## Quick Start

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php spark migrate
docker compose exec app php spark db:seed AuthSeeder
docker compose exec app php spark db:seed KioscoSeeder
docker compose exec app php spark db:seed TestDataSeeder
```

App available at http://localhost:3001

## Architecture

Service-Repository pattern with domain modules:

```
app/
  Modules/
    PublicPortal/    # Student self-service flow
    Stations/        # Capture stations (photo, signature, fingerprint)
    Admin/           # Dashboard and user management
    Auth/            # Login/logout
  Services/          # Business logic (TicketService, BiometricService, WebAuthnService, etc.)
  Repositories/      # Data access (TicketRepository, StudentRepository, etc.)
  Config/
    Schema.php       # Logical-to-physical schema mapping
```

## Default Credentials

| Username | Password | Role |
|---|---|---|
| admin | admin123 | Admin |
| prome | Temporal1234 | Admin |
| photo | changeme | Photo Station |
| signature | changeme | Signature Station |
| finger | changeme | Fingerprint Station |

## Operations

```bash
./deploy.sh setup      # Full production setup
./deploy.sh migrate    # Run migrations
./deploy.sh seed       # Run seeders
./deploy.sh backup     # Database backup
./deploy.sh keygen     # Generate encryption key
./deploy.sh status     # Service status
./deploy.sh logs       # Live logs
```

## Environment Variables

See `.env.example` for development. For production, copy `.env.production` and fill in:
- Database credentials
- `encryption.key` (generate with `php spark key:generate`)
- `CLOUDFLARE_TUNNEL_TOKEN`

## Key Flows

1. **Public self-service** (new): `/self-service/` → identify → confirm → signature → fingerprint → photo → success
2. **Public kiosk** (legacy): `/` → search student → generate ticket → signature → fingerprint → photo
3. **Capture stations**: `/stations/photo`, `/stations/signature`, `/stations/fingerprint` (auth required)
4. **Admin dashboard**: `/admin/` (auth required)
