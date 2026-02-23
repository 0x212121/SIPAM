# Audit Evidence Map

An internal government audit evidence web application for storing audit visit photos per Project, extracting GPS + taken_at from photo metadata (EXIF), displaying photo locations on a Leaflet map, and managing captions per photo with project-level access control and audit trails.

## Requirements

- Docker & Docker Compose
- Ports: 8080 (web), 5432 (PostgreSQL)

## Quick Start

1. Clone the repository and navigate to the project directory.

2. Copy environment file:
   ```bash
   cp .env.example .env
   ```

3. Build and start the containers:
   ```bash
   docker compose up -d --build
   ```

4. Install PHP dependencies:
   ```bash
   docker compose exec app composer install
   ```

5. Generate application key:
   ```bash
   docker compose exec app php artisan key:generate
   ```

6. Run migrations and seeders:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```

7. Access the application:
   - Web: http://localhost:8080
   - Login with one of the test accounts below

## Test Accounts

| Role     | Email                 | Password    |
|----------|----------------------|-------------|
| Admin    | admin@audit.local    | admin123    |
| Auditor  | auditor@audit.local  | auditor123  |
| Reviewer | reviewer@audit.local | reviewer123 |
| Readonly | readonly@audit.local | readonly123 |

## Queue Worker

The queue worker runs automatically in a separate container (`audit_queue`). To run manually:

```bash
docker compose exec app php artisan queue:work
```

## Useful Commands

```bash
# View logs
docker compose logs -f app

# Run tests
docker compose exec app php artisan test

# Reset database
docker compose exec app php artisan migrate:fresh --seed

# Enter app container
docker compose exec app bash
```

## Architecture Overview

- **Laravel 11** (PHP 8.3) - Web framework
- **PostgreSQL 16** - Database
- **Redis** - Queue backend
- **Nginx** - Web server
- **Spatie Laravel Permission** - RBAC
- **Leaflet** - Map library (frontend)
- **ExifTool** - EXIF metadata extraction

## Storage

Two private disks are used:
- `evidence_originals` - Original uploaded photos
- `evidence_derivatives` - Generated previews and thumbnails

All image access is served through authenticated controller routes.

## License

Proprietary - Internal Government Use Only
