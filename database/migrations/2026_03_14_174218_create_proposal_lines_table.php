<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')
                ->constrained('proposals')
                ->cascadeOnDelete();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->restrictOnDelete();
            // Optional preferred supplier for this line
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('entities')
                ->nullOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            // Cost price from the supplier (optional, for margin tracking)
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('proposal_id');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_lines');
    }
};
