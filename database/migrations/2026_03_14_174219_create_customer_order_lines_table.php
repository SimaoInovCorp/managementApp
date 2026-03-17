<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')
                ->constrained('customer_orders')
                ->cascadeOnDelete();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->restrictOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('entities')
                ->nullOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('customer_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_lines');
    }
};
