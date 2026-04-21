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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('date'); // Дата курса
            $table->decimal('value', 15, 4); // Курс валюты (130.0000)
            $table->decimal('vunit_rate', 15, 10); // Курс единицы (130.0000000000)
            $table->timestamps();

            $table->unique(['currency_id', 'date']);
            $table->index('date');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
