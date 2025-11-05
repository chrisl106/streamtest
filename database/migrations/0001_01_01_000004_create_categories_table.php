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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->enum('layout', ['grid', 'carousel'])->default('grid');
            $table->foreignId('tenant_id')->constrained('sites')->onDelete('cascade');
            $table->timestamps();

            $table->index(['order', 'tenant_id']);
            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
