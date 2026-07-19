<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_contents', function (Blueprint $table) {
            $table->id();
            $table->string('stage_key')->index();
            $table->unsignedTinyInteger('month')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('output_description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('youtube_url')->nullable();
            $table->string('uploaded_video_path')->nullable();
            $table->string('uploaded_video_original_name')->nullable();
            $table->string('uploaded_video_mime_type')->nullable();
            $table->unsignedInteger('uploaded_video_size')->default(0);
            $table->string('infographic_path')->nullable();
            $table->string('infographic_original_name')->nullable();
            $table->string('infographic_mime_type')->nullable();
            $table->unsignedInteger('infographic_size')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['stage_key', 'month', 'is_published']);
            $table->index(['stage_key', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_contents');
    }
};
