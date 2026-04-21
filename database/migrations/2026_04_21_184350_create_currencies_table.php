<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('char_code', 3)->unique(); // Код валюты (USD, EUR, RUB и т.п.)
            $table->string('name')->unique(); // Название валюты (Доллар США, Евро, Российский рубль и т.п.)
            $table->integer('nominal'); // Номинал (1, 10, 100, и т.п.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
