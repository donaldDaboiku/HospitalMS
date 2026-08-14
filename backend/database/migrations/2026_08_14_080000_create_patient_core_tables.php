<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('mrn', 32);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('date_of_birth');
            $table->string('gender', 32);
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 2)->default('NG');
            $table->string('occupation', 100)->nullable();
            $table->string('marital_status', 32)->nullable();
            $table->string('blood_group', 3)->nullable();
            $table->string('genotype', 3)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['hospital_id', 'mrn']);
            $table->index(['hospital_id', 'last_name', 'first_name']);
            $table->index(['hospital_id', 'phone']);
            $table->index(['hospital_id', 'date_of_birth']);
        });

        Schema::create('patient_mrn_sequences', function (Blueprint $table) {
            $table->foreignUuid('hospital_id')->primary()->constrained('hospitals')->cascadeOnDelete();
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
        });

        Schema::create('patient_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('full_name');
            $table->string('relationship', 64)->nullable();
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'type']);
        });

        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('allergen');
            $table->string('reaction')->nullable();
            $table->string('severity', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('patient_medical_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('condition_name');
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_identifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('number', 128);
            $table->string('issuer', 128)->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'type', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_identifications');
        Schema::dropIfExists('patient_medical_histories');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_contacts');
        Schema::dropIfExists('patient_mrn_sequences');
        Schema::dropIfExists('patients');
    }
};
