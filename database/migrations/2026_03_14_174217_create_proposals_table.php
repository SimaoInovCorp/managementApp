<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->date('proposal_date');
            // client_id references entities (client or both type)
            $table->foreignId('client_id')
                ->constrained('entities')
                ->restrictOnDelete();
            $table->date('validity_date');
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
        Schema::dropIfExists('proposals');
    }
};
