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
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->string('home_team');
            $table->string('away_team');
            $table->timestamp('date');
            $table->string('league');
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->string('venue')->nullable();
            $table->string('api_fixture_id')->nullable();
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['date', 'tenant_id']);
            $table->index(['status', 'tenant_id']);
            $table->index(['league', 'tenant_id']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
