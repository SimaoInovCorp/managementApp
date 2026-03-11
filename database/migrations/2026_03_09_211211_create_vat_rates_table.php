<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2)->default(0.00)->comment('Percentage, e.g. 23.00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_rates');
    }
};
