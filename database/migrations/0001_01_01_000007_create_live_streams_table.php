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
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('key')->unique();
            $table->boolean('is_live')->default(false);
            $table->boolean('record')->default(false);
            $table->string('recorded_path')->nullable();
            $table->integer('viewer_count')->default(0);
            $table->string('stream_url')->nullable();
            $table->boolean('chat_enabled')->default(true);
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['is_live', 'tenant_id']);
            $table->index(['key']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_streams');
    }
};
