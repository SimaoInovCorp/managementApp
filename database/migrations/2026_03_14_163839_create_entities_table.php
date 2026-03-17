<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `entities` table which holds both clients and suppliers.
 * Encrypted columns (nif, email, phone, mobile) cannot be indexed at the DB level;
 * uniqueness for `nif` is enforced at the application layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('client'); // client | supplier | both
            $table->unsignedBigInteger('number')->unique();
            $table->text('nif')->nullable();        // encrypted
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('locality')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->text('phone')->nullable();      // encrypted
            $table->text('mobile')->nullable();     // encrypted
            $table->string('website')->nullable();
            $table->text('email')->nullable();      // encrypted
            $table->boolean('gdpr_consent')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
