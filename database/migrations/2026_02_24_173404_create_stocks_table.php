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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();

            // Поля идентификации
            $table->string('nm_id')->index();
            $table->string('warehouse_name')->index();
            $table->string('barcode')->index();

            // Данные об остатках
            $table->integer('quantity')->default(0);
            $table->integer('quantity_full')->nullable();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();

            // Информация о товаре (может быть null в текущем ответе API)
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('sc_code')->nullable();

            // Цены
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();

            // Даты и флаги
            $table->date('date');
            $table->dateTime('last_change_date')->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();

            $table->timestamps();

            // Создаем уникальный индекс для upsert
            // Если nm_id, склад и баркод совпадают — это одна и та же запись
            $table->unique(['nm_id', 'warehouse_name', 'barcode'], 'stocks_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
