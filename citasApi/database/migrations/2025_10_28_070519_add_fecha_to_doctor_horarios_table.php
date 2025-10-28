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
        Schema::table('doctor_horarios', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('id_doctor');
            $table->unique(['id_doctor', 'fecha', 'hora_inicio', 'hora_fin'], 'unique_doctor_fecha_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_horarios', function (Blueprint $table) {
            $table->dropUnique('unique_doctor_fecha_slot');
            $table->dropColumn('fecha');
        });
    }
};
