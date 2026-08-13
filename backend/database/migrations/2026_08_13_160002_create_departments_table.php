<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->string('type', 64)->default('clinical');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hospital_id', 'code']);
            $table->index(['hospital_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
