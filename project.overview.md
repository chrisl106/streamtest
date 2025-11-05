Project Overview
Build a multi-tenant, self-hosted video streaming platform mimicking Uscreen but for personal use (no reselling). Core: A central dashboard to deploy isolated streaming sites (e.g., rugby-themed). Each site has Patreon-gated access (≥$2/month pledge), video management (VOD/live), admin panel with analytics, and user features like downloads.
Key Differentiators:

One-click site creation with theme/color picker.
Patreon OAuth with strict paid-check (≥200 cents/month).
Guests see only login page; no content leaks.
Admin-only: Full CRUD, bans, reports/charts.
Live HLS via OBS with recordings.
rclone/Pixeldrain for storage.
Selected ideas: Onboarding, continue watching, speed/quality controls, shortcuts, PWA offline, tip jar, series structure, revenue dashboard, funnel analytics, CDN switching, webhooks, mobile API, 2FA, rate limiting + device/IP security (max 2 devices, multi-IP pause/alert).
New: Fixtures page (API-Sports.io Rugby: upcoming 14 days + today, daily cron update, no past).

Assumptions:

VPS (Ubuntu 22.04, Docker installed).
Domains: dashboard.example.com for central; site1.example.com for sites (A-record to VPS IP).
Secrets: .env for Patreon creds, API keys, etc.
Multi-tenant: One Laravel codebase; per-site DB (MariaDB), MinIO bucket, Docker container.

Non-Goals: No comments, no PPV beyond tips, no geo-restrictions.

High-Level Architecture
text┌─────────────────────┐      ┌───────────────────────┐
│   Central Dashboard │─────▶│  Site Factory (CLI)   │
│  (Laravel + Inertia)│      │  (DB/Bucket/Nginx)    │
└───────▲─────────────┘      └─────────▲─────────────┘
        │                              │
        │  A-Record → VPS IP           │
        └───────────────▶ Nginx Proxy (TLS) ◀───────────────┐
                                        │                  │
        ┌───────────────────────────────────────────────────┴─────┐
        │                  Per-Site Instances                     │
        │  ┌─────────────┐ ┌──────────┐ ┌──────────────┐ ┌──────┐ │
        │  │  Laravel    │ │  Vue UI  │ │ Filament     │ │ Node │ │
        │  │  (Multi-Tenant)│         │ │ Admin Panel  │ │ (Jobs)│ │
        │  └──────▲──────┘ └──────▲───┘ └───────▲───────┘ └─▲───┘ │
        │         │              │              │         │     │
        │  ┌──────┴──────┐ ┌─────┴───┐ ┌────────┴────────┐ ┌────┴──┐ │
        │  │ MariaDB     │ │ Redis   │ │ MinIO (rclone)  │ │FFmpeg │ │
        │  │ (per site)  │ │ (Queues)│ │ (Pixeldrain)    │ │Worker │ │
        │  └─────────────┘ └─────────┘ └──────────────────┘ └──────┘ │
        └────────────────────────────────────────────────────────────┘

Central: Site creation/deploy.
Per-Site: Tenant-isolated (e.g., tenant_id in models).
Background: Horizon (Laravel) + BullMQ for transcoding/downloads.
Frontend: Inertia + Vue for SPA feel; Tailwind themes.
Security: Middleware for Patreon re-check (cached 5min), device limits (session + IP tracking).


Tech Stack & Dependencies






































































LayerToolsNotesBackendLaravel 11, Eloquent, HorizonMulti-tenant via stancl/tenancy or custom tenant_id.FrontendVue 3, Inertia.js, Tailwind CSSThemes via CSS vars; PWA manifest.AdminFilament v3Resources for Video/Category/Member/LiveStream; custom Analytics page with charts.AuthLaravel Socialite (Patreon), Fortify (2FA)Custom provider for Patreon paid-check.StorageMinIO (local S3), rclone (mount Pixeldrain)Videos in buckets; thumbnails auto-extract.Video/Livephp-ffmpeg, hls.js, MediamtxTranscode to HLS; OBS RTMP ingest.Queues/JobsRedis, Laravel Horizon, BullMQ (Node)Async: transcode, download, torrent, API cron.APILaravel Sanctum (JWT for mobile)Webhooks via routes.ChartsFilament Charts (Chart.js)Line/bar/doughnut for analytics.CDNBunnyCDN (integrate via config)Auto-switch in video URLs.Sports APIAPI-Sports.io RugbyFree key; cron job for fixtures.DeploymentDocker Compose, Nginx, CertbotPer-site containers; factory script.
Composer Requires: laravel/socialite filament/filament filament/charts laravel/horizon pbmedia/laravel-ffmpeg aler9/mediamtx minio/minio-php bullmq (etc.).
NPM: inertiajs/vue3 hls.js vue-chartjs.

Database Schema (Migrations)
Use php artisan make:migration for each. Key tables (per-tenant DB):

sites (central DB): id, name, slug, description, theme_id, main_color, accent_color.
themes (central): id, name, slug, css_variables (seed: Light/Dark/Movies/Sports/Rugby).
users: id, name, email, patreon_id, patreon_token, pledge_cents, is_admin, banned_at, devices_count, last_ip.
categories: id, name, order, layout (grid/carousel).
videos: id, title, description, category_id, source_type (local/url/embed/torrent), storage_path, hls_url, thumbnail, allow_download, series_id, views_count, total_minutes_watched.
series: id, title, season_number (for nested content).
video_views: id, video_id, user_id, seconds_watched, created_at.
live_streams: id, title, key, is_live, record, viewer_count, recorded_path.
members (synced from Patreon): id, user_id, pledge_cents.
fixtures: id, home_team, away_team, date, league, status (from API).
sessions (for device tracking): id, user_id, ip_address, user_agent, created_at.
webhook_logs: id, payload, status.

Relationships:

User hasMany VideoViews, belongsTo Category (admin).
Video belongsTo Category/Series, hasMany Views.
Site hasMany Users/Videos (multi-tenant filter).

Seed: 1 admin user, 3 themes, 5 demo videos in "Rugby Highlights" series, 10 fixtures.

Feature Breakdown & Implementation Guide
Implement in phases: Core → Auth → Admin → User Features → Ideas → Extras. Use middleware RequireAuth (Patreon check) on all non-login routes.
Phase 1: Core Platform (Central Dashboard + Site Factory)

Dashboard Routes: /sites (list), /sites/create (Vue form: name/desc/theme/logo/colors).
Factory Controller: On submit: Create Site model, new DB (site_{id}), MinIO bucket, Nginx vhost (template with server_name {slug}.example.com; proxy_pass http://container-port;), Docker Compose copy/up, Certbot. Output: "Add A-record for {slug}.example.com".
Multi-Tenant: Global scope on models: where('tenant_id', tenant()->id).
Docker: Base docker-compose.yml with services: app (PHP-FPM), nginx, mariadb, redis, minio, mediamtx, node (BullMQ). Per-site: ./sites/{slug}/docker-compose.yml with env TENANT_ID={id}.

Phase 2: Authentication & Security

Patreon Flow: Socialite driver (scopes: identity,campaigns). Callback: API call to /api/oauth2/v2/campaigns/{id}/members/{user_id} → check currently_entitled_amount_cents >= 200. Cache in User model (5min). Logout if invalid.
Guests: RequireAuth middleware redirects to /login (Patreon button only).
2FA (Idea 35): Fortify + laravel/fortify for TOTP on admin logins.
Rate Limiting (Idea 38): Throttle middleware: 5 logins/min, 10 downloads/hour.
Device/IP Security: On login, track sessions table (ip/user_agent). Limit: Query count(active_sessions) <= 2. Multi-IP: If >1 unique IP in 5min, pause session (redirect to verify), alert admin via email/Slack webhook.

Phase 3: Per-Site Frontend & Video Management

Themes: CSS vars in :root (e.g., --main: {site.main_color}; --accent: {site.accent_color};). Tailwind extend colors. Preview iframe in create form.
Login Page: Minimal, Patreon-only.
Home/Dashboard: Post-login: Onboarding wizard (Vue: pick categories → save prefs). Continue Watching row (query recent VideoViews). Categories grid/carousel.
Video Player: hls.js embed. Controls: Speed (0.5x-2x via playbackRate), Quality selector (HLS levels). Shortcuts (JS: keydown events). Download button if allow_download. No comments.
PWA (Idea 8): manifest.json, Service Worker (cache videos/assets).
Uploads (Admin-Only): Forms for local (rclone mount /mnt/pixeldrain), URL (job: curl → store), embed (URL only), torrent (aria2c → transcode). Auto-thumbnail: FFmpeg -ss 00:00:01 -vframes 1. Series: Nested select (Video belongsTo Series).
Live: Admin creates stream (gen key), OBS: rtmp://site/live/{key}. HLS output. "Live Now" banner. Optional recording (Mediamtx path).

Phase 4: Admin Panel (Filament)

Access: /admin (admin middleware: is_admin=true).
Resources:

Video: CRUD (edit/delete), columns (views/minutes).
Category: CRUD (order/layout).
Member: List/ban (toggle banned_at).
LiveStream: List (status/viewers), create key.


Analytics Page: Widgets (Filament Charts): Views line (30d), WatchTime bar (7d), TopVideos doughnut (10), LiveViewers bar. Track views: JS interval POST /api/track → VideoView model → observer increments counters.
Revenue Dashboard (Idea 25): Custom page: Total Patreon (sum pledges), Tips (Stripe integration).
Funnel Analytics (Idea 28): Chart: Visitor (logs) → Login → First Play → Retention. Use VideoViews timestamps.

Phase 5: Selected Ideas Implementation

1. Onboarding: Vue modal on first login: Multi-select categories → User prefs table. Filter home feed.
2. Continue Watching: Query VideoViews orderBy desc → row with progress bar ((seconds_watched / duration) * 100%).
4. Speed/Quality: hls.js events: hls.on(Hls.Events.LEVEL_SWITCHED, ...) for quality; video playbackRate slider.
5. Shortcuts: JS: document.addEventListener('keydown', e => { if (e.key === ' ') video.paused ? play() : pause(); }).
8. Offline (PWA): Cache API responses/videos (up to 3 recent).
10. Tip Jar: Stripe checkout during live (JS: create session → overlay button). Log to tips table for revenue dash.
14. Series/Seasons: Series model; Video season_number. Home: Expandable sections (e.g., "Season 1: Episodes").
25. Revenue: As above; query members + Stripe webhooks.
28. Funnel: Custom Filament widget: Steps as bar chart (counts from logs/views).
30. CDN Switch: Config cdn_provider (Bunny/Cloudflare); prepend to HLS URLs (e.g., https://bunny.net/{video.hls_url}).
32. Webhooks: Route /webhook/{type} (e.g., patreon-pledge-update). Log + trigger (e.g., email). Use Guzzle for outgoing.
33. Mobile API: Sanctum guards; endpoints /api/videos, /api/fixtures (JWT auth).
35. 2FA: Fortify setup; require on admin.
38. Security: As in Phase 2.

Phase 6: Fixtures Page

Route: /fixtures (public? No, auth-gated).
Model: Fixture (from API).
Cron Job: Laravel scheduler (php artisan schedule:run hourly): Guzzle to API-Sports /rugby/v1/fixtures?from={today}&to={today+14d}&league={rugby_ids} (e.g., Premiership=1218). Filter status != past, upsert to DB.
View: Vue table: Date, Home vs Away, League, Countdown (JS: moment.js). Update daily via cron. No past fixtures.
Config: .env RUGBY_API_KEY=xxx; leagues array for rugby focus.


Deployment & Ops

Host Prep: Ubuntu: Docker, Nginx, Certbot, rclone mount (rclone mount pixeldrain: /mnt/pixeldrain).
Scripts: deploy.sh {name} {theme}: Factory logic + docker-compose up -d.
Scaling: >10 sites → Swarm. Backups: Cron mysqldump + MinIO sync.
Monitoring: Horizon dashboard; alerts on queue fails.

Testing & Edge Cases

Unit: Auth flows, job queues (e.g., transcode success).
E2E: Cypress: Site create → login → upload → play → tip.
Edges: Invalid Patreon (redirect), 3rd device (block), API fail (cache fallback), offline play.

Generate the full code now, starting with composer create-project laravel/laravel stream-platform. Optimize for rugby: Default theme=Rugby, fixtures pre-loaded.