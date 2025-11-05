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
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('seconds_watched')->default(0);
            $table->boolean('completed')->default(false);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['video_id', 'user_id', 'tenant_id']);
            $table->index(['completed', 'tenant_id']);
            $table->index(['created_at', 'tenant_id']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
