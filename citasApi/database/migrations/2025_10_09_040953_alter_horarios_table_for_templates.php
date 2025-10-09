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
        Schema::table('horarios', function (Blueprint $table) {
            // Drop old columns
            $table->dropForeign(['id_doctor']);
            $table->dropColumn(['dia', 'id_doctor', 'disponible']);

            // Add new columns for templates
            $table->string('nombre');
            $table->json('dias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['nombre', 'dias']);

            // Add back old columns
            $table->string('dia');
            $table->foreignId('id_doctor')->constrained('doctores')->onDelete('cascade');
            $table->boolean('disponible')->default(true);
        });
    }
};
