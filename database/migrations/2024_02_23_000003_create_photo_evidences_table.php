<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('original_path');
            $table->string('preview_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->decimal('exif_lat', 10, 8)->nullable();
            $table->decimal('exif_lng', 11, 8)->nullable();
            $table->enum('gps_status', ['original', 'manual', 'missing'])->default('missing');
            $table->timestamp('taken_at')->nullable();
            $table->enum('taken_at_source', ['DateTimeOriginal', 'CreateDate', 'FileTimestamp', 'manual', 'missing'])->default('missing');
            $table->text('caption')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at');
            $table->string('sha256_hash', 64);
            $table->json('raw_exif_json')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'gps_status']);
            $table->index(['project_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_evidences');
    }
};
