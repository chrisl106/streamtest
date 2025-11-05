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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('device_fingerprint')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity');
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'tenant_id']);
            $table->index(['ip_address', 'tenant_id']);
            $table->index(['last_activity']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
