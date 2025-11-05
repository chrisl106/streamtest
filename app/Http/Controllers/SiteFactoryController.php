<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SiteFactoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the sites dashboard
     */
    public function index()
    {
        $sites = Site::with('theme')->paginate(10);

        return Inertia::render('Sites/Index', [
            'sites' => $sites,
        ]);
    }

    /**
     * Show the form for creating a new site
     */
    public function create()
    {
        $themes = Theme::all();

        return Inertia::render('Sites/Create', [
            'themes' => $themes,
        ]);
    }

    /**
     * Store a newly created site
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:sites,slug|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'theme_id' => 'required|exists:themes,id',
            'main_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        DB::beginTransaction();

        try {
            // Create the site record
            $site = Site::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'theme_id' => $request->theme_id,
                'main_color' => $request->main_color,
                'accent_color' => $request->accent_color,
                'database_name' => 'site_' . $site->id ?? Str::random(8),
                'minio_bucket' => 'site-' . $request->slug,
                'is_active' => false, // Will be activated after setup
            ]);

            // Generate database name and bucket
            $site->update([
                'database_name' => 'site_' . $site->id,
            ]);

            // Create the site infrastructure
            $this->createSiteInfrastructure($site);

            DB::commit();

            return redirect()->route('sites.index')
                ->with('message', 'Site created successfully! DNS setup required.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create site: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified site
     */
    public function show(Site $site)
    {
        $site->load('theme', 'users', 'videos', 'categories');

        return Inertia::render('Sites/Show', [
            'site' => $site,
        ]);
    }

    /**
     * Show the form for editing the specified site
     */
    public function edit(Site $site)
    {
        $themes = Theme::all();

        return Inertia::render('Sites/Edit', [
            'site' => $site,
            'themes' => $themes,
        ]);
    }

    /**
     * Update the specified site
     */
    public function update(Request $request, Site $site)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'theme_id' => 'required|exists:themes,id',
            'main_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_active' => 'boolean',
        ]);

        $site->update($request->only([
            'name', 'description', 'theme_id', 'main_color', 'accent_color', 'is_active'
        ]));

        return redirect()->route('sites.index')
            ->with('message', 'Site updated successfully.');
    }

    /**
     * Remove the specified site
     */
    public function destroy(Site $site)
    {
        // TODO: Clean up Docker containers, databases, etc.
        $site->delete();

        return redirect()->route('sites.index')
            ->with('message', 'Site deleted successfully.');
    }

    /**
     * Create the infrastructure for a new site
     */
    private function createSiteInfrastructure(Site $site): void
    {
        // Create database
        $this->createDatabase($site);

        // Create MinIO bucket
        $this->createMinioBucket($site);

        // Generate Nginx configuration
        $this->generateNginxConfig($site);

        // Create Docker Compose file
        $this->createDockerCompose($site);

        // TODO: Start Docker containers
        // $this->startDockerContainers($site);
    }

    private function createDatabase(Site $site): void
    {
        // This would create a separate database for the tenant
        // For now, we'll use a single database with tenant_id scoping
        DB::statement("CREATE DATABASE IF NOT EXISTS {$site->database_name}");
    }

    private function createMinioBucket(Site $site): void
    {
        // TODO: Create MinIO bucket via API
        // For now, just log the requirement
        \Illuminate\Support\Facades\Log::info("MinIO bucket creation required: {$site->minio_bucket}");
    }

    private function generateNginxConfig(Site $site): void
    {
        $config = $this->getNginxTemplate($site);

        $configPath = base_path("nginx/sites/{$site->slug}.conf");
        file_put_contents($configPath, $config);
    }

    private function createDockerCompose(Site $site): void
    {
        $compose = $this->getDockerComposeTemplate($site);

        $composePath = base_path("sites/{$site->slug}/docker-compose.yml");
        $composeDir = dirname($composePath);

        if (!is_dir($composeDir)) {
            mkdir($composeDir, 0755, true);
        }

        file_put_contents($composePath, $compose);
    }

    private function getNginxTemplate(Site $site): string
    {
        return "
server {
    listen 80;
    server_name {$site->slug}.example.com;

    location / {
        proxy_pass http://{$site->slug}_app:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location /hls/ {
        proxy_pass http://{$site->slug}_mediamtx:8888;
        proxy_set_header Host \$host;
    }
}
        ";
    }

    private function getDockerComposeTemplate(Site $site): string
    {
        return "
version: '3.8'
services:
  app:
    image: streaming-platform:latest
    container_name: {$site->slug}_app
    environment:
      - APP_NAME={$site->name}
      - DB_DATABASE={$site->database_name}
      - TENANT_ID={$site->id}
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - {$site->slug}_network

  nginx:
    image: nginx:alpine
    container_name: {$site->slug}_nginx
    ports:
      - '80'
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app
    networks:
      - {$site->slug}_network

  mediamtx:
    image: bluenviron/mediamtx:latest
    container_name: {$site->slug}_mediamtx
    ports:
      - '8888'
    volumes:
      - ./mediamtx.yml:/mediamtx.yml
    networks:
      - {$site->slug}_network

networks:
  {$site->slug}_network:
    driver: bridge
        ";
    }
}
