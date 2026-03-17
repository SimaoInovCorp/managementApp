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
        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->date('order_date');
            $table->foreignId('supplier_id')
                ->constrained('entities')
                ->restrictOnDelete();
            // Nullable: supplier order may be created independently of a customer order
            $table->foreignId('customer_order_id')
                ->nullable()
                ->constrained('customer_orders')
                ->nullOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('number');
            $table->index('customer_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_orders');
    }
};
