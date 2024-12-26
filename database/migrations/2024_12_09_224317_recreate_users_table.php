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
        // Lösche die existierende Tabelle
        Schema::dropIfExists('users');

        // Erstelle die neue Tabelle
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->boolean('approved')->default(false);
            $table->text('steueridentifikationsnummer')->nullable();
            $table->text('street')->nullable();
            $table->integer('number')->nullable(); // string
            $table->text('pzl')->nullable();
            $table->text('city')->nullable();
            $table->text('country')->nullable();
            $table->text('bank_name')->nullable();
            $table->text('iban')->nullable();
            $table->integer('bic')->nullable(); // string
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
