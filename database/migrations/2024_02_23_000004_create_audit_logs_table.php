<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('entity');
            $table->enum('action', ['create', 'update', 'delete', 'lock', 'status_change']);
            $table->string('field')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->foreignId('actor_user_id')->constrained('users');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
