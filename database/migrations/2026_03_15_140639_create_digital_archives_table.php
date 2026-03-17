<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_archives', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                      // Human-readable filename / title
            $table->string('path');                                      // Storage path on private disk
            $table->string('category', 100)->nullable()->index();        // Optional grouping label
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('entity_id');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_archives');
    }
};
