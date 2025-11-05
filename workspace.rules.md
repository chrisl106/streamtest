Self-Hosted Streaming Platform Guidelines
Project Overview

This is a multi-tenant Laravel 11 application for deploying isolated streaming sites.
Core features: Site creation dashboard, Patreon-gated access (≥$2/month), video/live management, admin analytics with charts, user features like onboarding and downloads.
Tech stack: Laravel 11, Vue 3 with Inertia.js, Filament v3, Docker Compose, Nginx, MinIO, Redis, FFmpeg, Mediamtx, rclone for Pixeldrain, BullMQ for jobs.
Focus on rugby/sports themes; no comments on videos; guests see only login page.
Implement selected ideas: Onboarding wizard, continue watching, speed/quality controls, keyboard shortcuts, PWA offline, tip jar, series structure, revenue dashboard, funnel analytics, CDN switching, webhooks, mobile API, 2FA, rate limiting with device/IP security, upcoming fixtures page from API-Sports.io.

Folder Structure

Follow Laravel conventions with extensions:

/app/Models: Eloquent models (e.g., Site, Theme, Video, User, Fixture).
/app/Http/Controllers: Separate central (e.g., SiteFactoryController) and per-site (e.g., VideoController).
/app/Http/Middleware: Custom like RequireAuth, Admin, TenantScope.
/app/Services: Business logic (e.g., PatreonService, VideoProcessingService).
/app/Jobs: Queueable (e.g., ProcessUrlVideo, TranscodeVideo).
/app/Observers: E.g., VideoViewObserver for analytics.
/app/Filament: Resources and pages (e.g., VideoResource, AnalyticsDashboard).
/resources/js: Vue components (e.g., Pages/Sites/Create.vue).
/resources/views: Blade layouts (e.g., app.blade.php with theme vars).
/database/migrations: Tenant-specific tables.
/database/seeders: Demo data (themes, videos, fixtures).
/sites/{slug}: Per-site Docker files.
/nginx: Vhost templates.
/docs: Feature docs, ADRs for architecture decisions.


Use .env for secrets (Patreon creds, API keys, etc.).
Store rules in .clinerules/ with numeric prefixes (e.g., 01-core.md, 02-security.md).

Code Style & Patterns

Follow Laravel coding standards: PSR-12, 4-space indentation, snake_case for variables/methods.
Use repository pattern for data access: E.g., VideoRepository in /app/Repositories for CRUD, queried with tenant_id scope.
Prefer composition: E.g., VideoService composes FFmpeg and Storage traits.
Async everything heavy: Use Horizon/BullMQ for transcoding, downloads, torrent processing, API cron.
Multi-tenant: Apply global scope where('tenant_id', tenant()->id) on models; use stancl/tenancy if needed.
API: Sanctum JWT for mobile endpoints (e.g., /api/videos).
Frontend: Inertia for SPA; Tailwind with CSS vars for themes/colors.
Error handling: Validate requests, catch exceptions (e.g., Patreon API fails → logout), log to Redis.
Naming: Descriptive (e.g., handlePatreonCallback, isPatreonPaid).

Documentation Requirements

Update /docs for each feature: E.g., /docs/patreon-integration.md with flow diagrams.
Maintain CHANGELOG.md: Entries like "Added fixtures page with API-Sports.io integration".
ADRs in /docs/adrs: E.g., "Why Mediamtx for live streaming".
Inline docs: PHPDoc for methods, Vue comments for components.
README.md: Setup guide, deploy script usage, tool versions.

Testing Practices

Unit tests: Auth (Patreon check), jobs (transcode), services (e.g., PatreonService::isPaidMember).
Feature tests: Site creation, login flow, video upload/play, fixtures cron.
Integration: Docker end-to-end (e.g., OBS stream → HLS play).
Coverage: Aim 80%+; use PHPUnit.
Edges: Invalid pledge (<$2), 3rd device login (block), API fail (cache fallback).

Security Guidelines

Auth: Patreon OAuth with cached paid-check (≥200 cents); 2FA via Fortify for admins.
Rate limiting: 5 logins/min, 10 downloads/hour.
Device/IP: Track sessions; max 2 active; multi-IP (>1 in 5min) → pause session, email admin alert.
Middleware: RequireAuth on all routes; Admin for Filament.
Storage: Signed URLs for videos (expire 1h).
Inputs: Validate/sanitize all (e.g., video titles).

Feature-Specific Rules

Onboarding (Idea 1): Vue modal on first login; save category prefs to User model.
Continue Watching (Idea 2): Query VideoViews; show progress bar in home row.
Player Controls (Ideas 4/5): hls.js for speed/quality; JS keydown for shortcuts.
PWA Offline (Idea 8): Manifest.json, Service Worker cache (videos/assets).
Tip Jar (Idea 10): Stripe checkout in live view; log to tips table.
Series (Idea 14): Nested models; expandable UI sections.
Dashboards (Ideas 25/28): Filament pages with Chart.js widgets (views, revenue, funnels).
CDN (Idea 30): Configurable provider; prepend to URLs.
Webhooks (Idea 32): Routes for events (e.g., /webhook/patreon); log and trigger actions.
Mobile API (Idea 33): Sanctum-protected endpoints.
Fixtures Page: Cron hourly: Guzzle API-Sports.io (/rugby/v1/fixtures?from=today&to=+14d); store in DB; Vue table with countdown; rugby leagues focus.
Live Streaming: Mediamtx per-site; admin gen key; optional record.

Deployment & Ops

Use deploy.sh for site creation: Copy Docker, gen Nginx, Certbot.
Cron: Fixtures update, backups (mysqldump + MinIO).
Monitoring: Horizon dashboard; alerts on fails.

Apply these rules contextually during code generation for the streaming platform.