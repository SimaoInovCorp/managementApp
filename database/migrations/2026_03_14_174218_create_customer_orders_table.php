<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->date('order_date');
            $table->foreignId('client_id')
                ->constrained('entities')
                ->restrictOnDelete();
            // Nullable: order may be created independently of any proposal
            $table->foreignId('proposal_id')
                ->nullable()
                ->constrained('proposals')
                ->nullOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
