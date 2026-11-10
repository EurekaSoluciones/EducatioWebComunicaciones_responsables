<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_supervielle_publications', function (Blueprint $table) {
            $table->uuid('attempt_id')->primary();
            $table->uuid('hash')->nullable();
            $table->string('client', 30);
            $table->string('agreement', 30);
            $table->string('student_id', 30);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->timestamps();
        });
        // SQL Server needs a filtered index to permit several unpublished attempts.
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('CREATE UNIQUE INDEX web_sv_publication_hash ON web_supervielle_publications (hash) WHERE hash IS NOT NULL');
        } else {
            Schema::table('web_supervielle_publications', fn (Blueprint $table) => $table->unique('hash', 'web_sv_publication_hash'));
        }
        Schema::create('web_supervielle_payments', function (Blueprint $table) {
            $table->id();
            $table->string('client', 30);
            $table->string('agreement', 30);
            $table->string('payment_id', 30);
            $table->uuid('attempt_id');
            $table->foreign('attempt_id')->references('attempt_id')->on('web_supervielle_publications');
            $table->unsignedSmallInteger('bank_status');
            $table->unsignedSmallInteger('payment_method');
            $table->decimal('amount', 12, 2);
            $table->dateTime('paid_at');
            $table->dateTime('posted_at')->nullable();
            $table->boolean('needs_review')->default(false);
            $table->timestamps();
            $table->unique(['client', 'agreement', 'payment_id'], 'web_sv_payment_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_supervielle_payments');
        Schema::dropIfExists('web_supervielle_publications');
    }
};
