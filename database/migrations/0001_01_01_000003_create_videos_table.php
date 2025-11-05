<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->enum('source_type', ['local', 'url', 'embed', 'torrent'])->default('local');
            $table->string('storage_path')->nullable();
            $table->string('hls_url')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('allow_download')->default(false);
            $table->foreignId('series_id')->nullable()->constrained('series');
            $table->integer('views_count')->default(0);
            $table->integer('total_minutes_watched')->default(0);
            $table->integer('duration')->nullable(); // in seconds
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['category_id', 'tenant_id']);
            $table->index(['series_id', 'tenant_id']);
            $table->index(['views_count', 'tenant_id']);
            $table->index(['created_at', 'tenant_id']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
