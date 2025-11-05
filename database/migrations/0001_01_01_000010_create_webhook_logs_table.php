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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['type', 'status', 'tenant_id']);
            $table->index(['status', 'tenant_id']);
            $table->index(['created_at', 'tenant_id']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
