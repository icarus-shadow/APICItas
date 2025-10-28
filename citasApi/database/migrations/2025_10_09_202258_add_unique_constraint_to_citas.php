<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar duplicados antes de agregar la restricción única
        DB::delete('DELETE FROM citas WHERE id IN (SELECT t1.id FROM citas t1 INNER JOIN citas t2 ON t1.id > t2.id WHERE t1.id_doctor = t2.id_doctor AND t1.fecha_cita = t2.fecha_cita AND t1.hora_cita = t2.hora_cita)');

        Schema::table('citas', function (Blueprint $table) {
            $table->unique(['id_doctor', 'fecha_cita', 'hora_cita']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique(['id_doctor', 'fecha_cita', 'hora_cita']);
        });
    }
};
