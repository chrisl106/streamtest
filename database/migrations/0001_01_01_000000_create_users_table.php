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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('patreon_id')->nullable()->unique();
            $table->text('patreon_token')->nullable();
            $table->integer('pledge_cents')->default(0);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('banned_at')->nullable();
            $table->integer('devices_count')->default(0);
            $table->string('last_ip')->nullable();
            $table->json('category_prefs')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('sites')->onDelete('cascade');
            $table->rememberToken();
            $table->timestamps();

            $table->index(['patreon_id', 'tenant_id']);
            $table->index(['is_admin', 'tenant_id']);
            $table->index(['banned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
