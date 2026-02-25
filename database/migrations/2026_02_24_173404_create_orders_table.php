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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Уникальный номер заказа из API
            $table->string('g_number')->unique();

            // Внешние ключи
            $table->unsignedBigInteger('barcode')->index();
            $table->unsignedBigInteger('nm_id')->index();
            $table->unsignedBigInteger('income_id')->nullable();
            $table->string('odid')->nullable();

            // Даты
            $table->dateTime('date'); // Здесь приходит и дата и время
            $table->date('last_change_date');
            $table->dateTime('cancel_dt')->nullable(); // Дата отмены

            // Товар
            $table->string('supplier_article');
            $table->string('tech_size');
            $table->string('subject');
            $table->string('category');
            $table->string('brand');

            // Финансы
            $table->decimal('total_price', 12, 2);
            $table->integer('discount_percent');

            // География и статус
            $table->string('warehouse_name');
            $table->string('oblast');
            $table->boolean('is_cancel')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
