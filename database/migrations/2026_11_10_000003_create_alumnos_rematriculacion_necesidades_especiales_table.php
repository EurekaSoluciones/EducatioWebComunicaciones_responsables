<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv';

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('dbo.AlumnosRematriculacion_NecesidadesEspeciales', function (Blueprint $table) {
            $table->integer('Cod_Rematriculacion');
            $table->integer('Cod_NecesidadEspecial');
            $table->string('descripcion_otra', 500)->nullable();

            $table->primary(['Cod_Rematriculacion', 'Cod_NecesidadEspecial'], 'pk_arne');
            $table->index('Cod_NecesidadEspecial', 'ix_arne_necesidad');

            $table->foreign('Cod_Rematriculacion', 'fk_arne_rematriculacion')
                ->references('Codigo')
                ->on('dbo.AlumnosRematriculacion')
                ->onDelete('no action')
                ->onUpdate('no action');

            $table->foreign('Cod_NecesidadEspecial', 'fk_arne_necesidad')
                ->references('Cod_NecesidadEspecial')
                ->on('dbo.Tipo_NecesidadesEspeciales')
                ->onDelete('no action')
                ->onUpdate('no action');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('dbo.AlumnosRematriculacion_NecesidadesEspeciales');
    }
};
