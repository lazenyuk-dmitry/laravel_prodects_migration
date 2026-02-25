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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Уникальный ключ продажи из API
            $table->string('sale_id')->unique();

            // Внешние ключи и идентификаторы
            $table->string('g_number');
            $table->unsignedBigInteger('barcode')->index();
            $table->unsignedBigInteger('nm_id')->index();
            $table->integer('income_id')->default(0);
            $table->string('odid')->nullable();

            // Даты
            $table->date('date');
            $table->dateTime('last_change_date');

            // Информация о товаре
            $table->string('supplier_article');
            $table->string('tech_size');
            $table->string('subject');
            $table->string('category');
            $table->string('brand');

            // Финансы (используем decimal для точности)
            $table->decimal('total_price', 12, 2);
            $table->integer('discount_percent');
            $table->decimal('spp', 12, 2)->default(0);
            $table->decimal('for_pay', 12, 2);
            $table->decimal('finished_price', 12, 2);
            $table->decimal('price_with_disc', 12, 2);
            $table->decimal('promo_code_discount', 12, 2)->nullable();

            // Логистика и флаги
            $table->string('warehouse_name');
            $table->string('country_name');
            $table->string('oblast_okrug_name');
            $table->string('region_name');
            $table->boolean('is_supply');
            $table->boolean('is_realization');
            $table->boolean('is_storno')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
