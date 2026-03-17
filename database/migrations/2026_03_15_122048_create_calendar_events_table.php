<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->time('time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('entity_id')->nullable()->nullOnDelete()->constrained('entities');
            $table->foreignId('type_id')->nullable()->nullOnDelete()->constrained('calendar_types');
            $table->foreignId('action_id')->nullable()->nullOnDelete()->constrained('calendar_actions');
            $table->text('description')->nullable();
            $table->json('shared_with')->nullable(); // array of user IDs
            $table->text('knowledge')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('confirmed');
            $table->timestamps();

            $table->index('user_id');
            $table->index('entity_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
