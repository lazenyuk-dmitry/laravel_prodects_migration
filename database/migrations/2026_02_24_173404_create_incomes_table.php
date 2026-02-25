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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();

            // Уникальный ID поставки (из API)
            $table->unsignedBigInteger('income_id');
            $table->string('number')->nullable(); // Номер УПД или накладной

            // Связи
            $table->string('barcode')->index();
            $table->string('nm_id')->index();

            // Даты
            $table->date('date'); // Дата создания
            $table->date('last_change_date');
            $table->date('date_close')->nullable(); // Дата закрытия поставки

            // Товар и количество
            $table->string('supplier_article');
            $table->string('tech_size');
            $table->integer('quantity');
            $table->decimal('total_price', 12, 2)->default(0);

            $table->string('warehouse_name');

            $table->timestamps();

            // Создаем уникальный индекс для upsert
            // Одна поставка может содержать разные товары (баркуоды)
            $table->unique(['income_id', 'barcode'], 'incomes_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
