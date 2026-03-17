<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->date('date');
            $table->timestamp('created_at')->useCurrent(); // append-only: no updated_at

            $table->index('entity_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
