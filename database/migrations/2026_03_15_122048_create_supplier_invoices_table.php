<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('supplier_id')->constrained('entities')->restrictOnDelete();
            $table->foreignId('supplier_order_id')->nullable()->nullOnDelete()->constrained('supplier_orders');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('document_path')->nullable();       // uploaded invoice document
            $table->string('payment_proof_path')->nullable();  // proof of payment sent to supplier
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
