<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the contacts table.
     *
     * `phone`, `mobile`, and `email` are stored encrypted (non-deterministic AES),
     * so they use the `text` column type and cannot carry a unique index.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('number')->unique();
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->foreignId('role_id')->nullable()->constrained('contact_roles')->nullOnDelete();
            $table->text('phone')->nullable();   // encrypted
            $table->text('mobile')->nullable();  // encrypted
            $table->text('email')->nullable();   // encrypted
            $table->boolean('gdpr_consent')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index('entity_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
