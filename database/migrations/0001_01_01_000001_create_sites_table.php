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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('theme_id')->constrained('themes');
            $table->string('main_color', 7)->nullable(); // Hex color
            $table->string('accent_color', 7)->nullable(); // Hex color
            $table->string('domain')->nullable();
            $table->string('database_name')->nullable();
            $table->string('minio_bucket')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
