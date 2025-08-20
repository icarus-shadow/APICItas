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
        Schema::create('doctores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('especialidad');
            $table->string('horario')->nullable();
            $table->string('lugar_trabajo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
        /**
         * Reverse the migrations.
         */
    {
        Schema::dropIfExists('doctores');
    }
};
