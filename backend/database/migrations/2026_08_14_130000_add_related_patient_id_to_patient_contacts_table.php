<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_contacts', function (Blueprint $table) {
            $table->foreignUuid('related_patient_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('patients')
                ->nullOnDelete();
            $table->index(['related_patient_id']);
        });
    }

    public function down(): void
    {
        Schema::table('patient_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_patient_id');
        });
    }
};
