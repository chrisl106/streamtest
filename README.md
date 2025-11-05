# StreamTest - Multi-Tenant Video Streaming Platform

A self-hosted, multi-tenant video streaming platform similar to Uscreen but for personal use. Built with Laravel 11, Vue 3, and Inertia.js.

## Features

- **Multi-Tenant Architecture**: Create isolated streaming sites with custom themes
- **Patreon Integration**: OAuth authentication with pledge verification (≥$2/month)
- **Video Management**: VOD uploads, HLS streaming, live streaming via OBS
- **Admin Dashboard**: Full CRUD operations, analytics, user management
- **Device Security**: IP tracking, session limits, rate limiting
- **Rugby Focus**: Sports fixtures integration, themed UI
- **PWA Support**: Offline viewing, mobile optimization

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Vue 3, Inertia.js, Tailwind CSS
- **Database**: MySQL/MariaDB with tenant scoping
- **Storage**: MinIO S3-compatible, rclone for Pixeldrain
- **Video**: FFmpeg, HLS.js, MediaMTX for live streaming
- **Deployment**: Docker Compose, Nginx reverse proxy

## Quick Start

1. **Clone and Setup**
   ```bash
   git clone https://github.com/chrisl106/streamtest.git
   cd streamtest
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build Assets**
   ```bash
   npm run build
   ```

6. **Start Development Server**
   ```bash
   php artisan serve
   ```

## Configuration

### Patreon OAuth
Set these in your `.env`:
```
PATREON_CLIENT_ID=your_client_id
PATREON_CLIENT_SECRET=your_client_secret
PATREON_CAMPAIGN_ID=your_campaign_id
```

### MinIO Storage
```
MINIO_ENDPOINT=http://localhost:9000
MINIO_ACCESS_KEY=your_access_key
MINIO_SECRET_KEY=your_secret_key
```

## Project Structure

```
app/
├── Models/           # Eloquent models with tenant scoping
├── Http/Controllers/ # Controllers for sites, videos, auth
├── Services/         # Business logic (Patreon, video processing)
├── Middleware/       # Auth, admin, tenant middleware
database/
├── migrations/       # Multi-tenant database schema
├── seeders/          # Theme and demo data
resources/
├── js/Pages/         # Vue components
├── css/             # Tailwind styles
routes/
├── web.php          # Site routes with tenant middleware
├── api.php          # API endpoints
sites/               # Per-site Docker configurations
nginx/               # Reverse proxy configurations
```

## Deployment

The platform is designed for Docker deployment:

1. **Central Dashboard**: Manages site creation
2. **Per-Site Containers**: Isolated Laravel instances
3. **Nginx Proxy**: Routes traffic to appropriate containers
4. **MinIO**: S3-compatible storage for videos
5. **MediaMTX**: Live streaming server

## Security Features

- Patreon pledge verification (cached 5min)
- Device session limits (max 2 active)
- IP address monitoring
- Rate limiting on downloads/uploads
- Admin role-based access
- Input validation and sanitization

## Contributing

1. Follow PSR-12 coding standards
2. Use repository pattern for data access
3. Write tests for new features
4. Update documentation

## License

MIT License - see LICENSE file for details.
