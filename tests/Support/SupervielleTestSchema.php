<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

// Isolated test fixture. Production migrations belong to the docentes module.
class SupervielleTestSchema
{
    public static function create(): void
    {
        if (! app()->environment('testing') || DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new LogicException('El esquema de prueba solo se permite en SQLite en memoria.');
        }
        Schema::create('web_supervielle_publications', function (Blueprint $table) {
            $table->uuid('attempt_id')->primary();
            $table->uuid('hash')->nullable()->unique();
            $table->string('client', 30);
            $table->string('agreement', 30);
            $table->string('student_id', 30);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->timestamps();
        });
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
            $table->unique(['client', 'agreement', 'payment_id']);
        });
    }
}
