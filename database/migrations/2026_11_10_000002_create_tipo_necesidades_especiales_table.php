<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv';

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('dbo.Tipo_NecesidadesEspeciales', function (Blueprint $table) {
            $table->increments('Cod_NecesidadEspecial');
            $table->string('descripcion', 255);
            $table->dateTime('fecha_alta')->useCurrent();
            $table->dateTime('fecha_baja')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('dbo.Tipo_NecesidadesEspeciales');
    }
};
