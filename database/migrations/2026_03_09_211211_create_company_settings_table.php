<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row settings table — enforced via application logic (findOrNew)
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('locality', 100)->nullable();
            $table->string('tax_number', 20)->nullable()->comment('NIF / VAT number of the company');
            $table->string('logo_path')->nullable()->comment('Path within the private storage disk');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
