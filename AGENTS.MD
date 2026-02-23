# agents.md — Audit Evidence Map (MVP)

## Mission
Build an internal government audit evidence web app that stores audit visit photos per Project, extracts GPS + taken_at from photo metadata (EXIF), displays photo locations on a Leaflet map, and allows captions per photo. The app must enforce project-level access control and keep an audit trail of changes.

## Non-negotiables
- Laravel 11 (PHP 8.3), PostgreSQL 16
- Local filesystem storage only (NO MinIO/S3)
- Originals MUST be private (no public URLs)
- All image access must be served through authenticated controller routes with authorization checks
- Leaflet + marker clustering
- EXIF extraction server-side using `exiftool` (preferred) with fallback to PHP `exif_read_data`
- Thumbnail/preview generation must be async via Laravel queue (database driver OK for MVP)
- Every update that matters must write to `audit_logs`

## Output expectations
- Produce a runnable repo with:
  - docker-compose (app + postgres)
  - migrations + seeders
  - minimal Blade UI
  - queue worker instructions
  - minimal automated tests (permissions + EXIF)
  - README with setup and commands

## Architecture
### Storage
- Use two disks:
  - `evidence_originals` (private): store original photos
  - `evidence_derivatives` (private): store previews + thumbnails
- Do not use `php artisan storage:link` for these disks unless explicitly approved.
- Implement streaming endpoints:
  - `/projects/{project}/photos/{photo}/original`
  - `/projects/{project}/photos/{photo}/preview`
  - `/projects/{project}/photos/{photo}/thumb`
- Endpoints must check:
  - authenticated user
  - user is admin OR project member
  - project is accessible
- Use `Storage::disk()->readStream()` and set proper headers.

### Database schema (minimum)
- users
- roles/permissions (spatie)
- projects:
  - code (unique), name, auditee_agency, period_start, period_end, status (draft|review|final), created_by
- project_members:
  - project_id, user_id, role_in_project (auditor|reviewer|readonly)
- photo_evidences:
  - project_id
  - original_path, preview_path, thumb_path
  - exif_lat, exif_lng (nullable)
  - gps_status (original|manual|missing)
  - taken_at (nullable), taken_at_source (DateTimeOriginal|CreateDate|FileTimestamp|manual|missing)
  - caption (nullable text), tags (nullable json or string)
  - uploaded_by, uploaded_at
  - sha256_hash
  - raw_exif_json (nullable json)
- audit_logs:
  - entity_type (project|photo), entity_id
  - action (create|update|delete|lock|status_change)
  - field (nullable), old_value (nullable), new_value (nullable)
  - actor_user_id, created_at

### Authorization rules
- System roles: admin, auditor, reviewer, readonly
- Project access:
  - admin can access everything
  - non-admin must be in `project_members` to view/export/stream photos
- Status rules:
  - reviewer (and admin) can change project status to `final`
  - when `final`, block edits to PhotoEvidence (caption/location/taken_at) for non-admin
- Every denied access returns 403.

## Features (MVP)
### 1) Auth + RBAC + Project permissions
- Login screen (simple)
- Seed 4 users with known passwords (admin/auditor/reviewer/readonly)
- Enforce project-level permission checks via Policies

### 2) Project management UI
- /projects list (filter by status)
- /projects/new create
- /projects/{id} detail with tabs:
  - Map
  - Gallery
  - Audit Log
- Manage project members (admin or project creator)

### 3) Batch upload photos
- Multi-file upload on project detail page
- For each uploaded image:
  - compute sha256
  - store to `evidence_originals`
  - extract EXIF gps + taken_at
  - create PhotoEvidence with gps_status/taken_at_source
  - dispatch job to generate preview + thumb into `evidence_derivatives`
- If EXIF missing:
  - gps_status=missing
  - taken_at_source=missing (or fallback to file timestamp)
- Store raw EXIF JSON if available.

### 4) Gallery + Photo detail
- Gallery grid with pagination/lazy load
- Photo detail:
  - preview image
  - metadata panel (GPS, taken_at + sources)
  - edit caption
  - set manual taken_at
  - show audit history for this photo
- All changes write to audit_logs.

### 5) Map
- Leaflet map shows markers only for photos with lat/lng
- Use marker clustering
- Marker popup shows thumb + caption + taken_at + link to photo detail
- Filters (apply to map + gallery):
  - date range
  - tags
  - only-with-gps toggle

### 6) Manual location
- On photo detail page provide a Leaflet map; clicking sets lat/lng
- Update photo: gps_status=manual
- Log changes in audit_logs
- Block if project final and user not admin

### 7) Export
- `/projects/{id}/export.csv`
- `/projects/{id}/export.geojson`
- Only project members/admin
- CSV fields: stored path, lat, lng, gps_status, taken_at, taken_at_source, caption, uploaded_by, uploaded_at, sha256

## Implementation plan (do in order)
1. Bootstrap Laravel app + docker-compose + Postgres
2. Migrations + models + seeders (users/roles/projects)
3. Auth UI + middleware
4. Policies for Project and PhotoEvidence + audit log helper
5. Project CRUD + members management
6. Upload endpoint + EXIF service + sha256 + create PhotoEvidence
7. Queue job for preview/thumb generation
8. Streaming controllers for original/preview/thumb
9. Map + Gallery pages + filters
10. Manual location + manual taken_at
11. Export endpoints (CSV/GeoJSON)
12. Tests + README

## Definition of done
- `docker compose up` brings up app + postgres
- `php artisan migrate --seed` creates test users and sample project
- Users without project membership get 403 on:
  - project detail
  - exports
  - image streaming endpoints
- Upload a photo with GPS shows marker on map
- Upload a photo without GPS still appears in gallery with gps_status=missing
- Caption and manual location edits create audit log entries
- Setting project status to final locks edits for non-admin
- CSV and GeoJSON exports work and are authorization-protected
- Minimal automated tests pass

## Constraints / Coding rules
- Keep UI minimal but usable (Blade)
- Use Zod-like validation equivalents in Laravel (Form Requests)
- Avoid over-engineering (max 20 users)
- Prefer clarity over cleverness