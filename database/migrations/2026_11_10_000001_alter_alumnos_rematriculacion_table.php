<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'sqlsrv';

    public function up(): void
    {
        DB::connection($this->getConnection())->statement(
            'ALTER TABLE dbo.AlumnosRematriculacion ADD CONSTRAINT PK_AlumnosRematriculacion_Codigo_NE PRIMARY KEY NONCLUSTERED (Codigo)'
        );

        DB::connection($this->getConnection())->statement(
            'ALTER TABLE dbo.AlumnosRematriculacion ADD [año_lectivo] INT NULL'
        );
    }

    public function down(): void
    {
        DB::connection($this->getConnection())->statement(
            'ALTER TABLE dbo.AlumnosRematriculacion DROP COLUMN [año_lectivo]'
        );

        DB::connection($this->getConnection())->statement(
            'ALTER TABLE dbo.AlumnosRematriculacion DROP CONSTRAINT PK_AlumnosRematriculacion_Codigo_NE'
        );
    }
};
