<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('category', 64)->nullable();
            $table->string('specimen_type', 64)->nullable();
            $table->string('unit', 32)->nullable();
            $table->string('reference_range')->nullable();
            $table->unsignedSmallInteger('turnaround_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hospital_id', 'code']);
            $table->index(['hospital_id', 'is_active']);
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignUuid('ordered_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 32)->default('ordered');
            $table->string('priority', 16)->default('routine');
            $table->text('clinical_notes')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index(['hospital_id', 'patient_id']);
            $table->index(['hospital_id', 'ordered_at']);
        });

        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignUuid('lab_test_id')->constrained('lab_tests')->restrictOnDelete();
            $table->string('status', 32)->default('ordered');
            $table->timestamps();

            $table->unique(['lab_order_id', 'lab_test_id']);
        });

        Schema::create('lab_specimens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('collected_by')->constrained('users')->restrictOnDelete();
            $table->string('specimen_type', 64);
            $table->string('status', 32)->default('collected');
            $table->timestamp('collected_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'lab_order_id']);
        });

        Schema::create('lab_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('lab_order_item_id')->constrained('lab_order_items')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('entered_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('value');
            $table->string('unit', 32)->nullable();
            $table->string('flag', 16)->default('normal');
            $table->string('status', 32)->default('preliminary');
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['lab_order_item_id']);
            $table->index(['hospital_id', 'status']);
        });

        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('encounter_id')->nullable()->constrained('encounters')->nullOnDelete();
            $table->foreignUuid('ordered_by')->constrained('users')->restrictOnDelete();
            $table->string('modality', 32);
            $table->string('study_name');
            $table->string('status', 32)->default('ordered');
            $table->string('priority', 16)->default('routine');
            $table->text('clinical_indication')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index(['hospital_id', 'patient_id']);
        });

        Schema::create('radiology_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('radiology_order_id')->constrained('radiology_orders')->cascadeOnDelete();
            $table->foreignUuid('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('findings');
            $table->text('impression')->nullable();
            $table->string('status', 32)->default('final');
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->unique(['radiology_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_reports');
        Schema::dropIfExists('radiology_orders');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_specimens');
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
    }
};
