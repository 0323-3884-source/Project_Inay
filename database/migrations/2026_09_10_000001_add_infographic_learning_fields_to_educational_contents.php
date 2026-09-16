<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educational_contents', function (Blueprint $table) {
            $table->string('category', 100)->nullable();
            $table->unsignedTinyInteger('calendar_month')->nullable();
            $table->string('infographic_key', 160)->nullable()->unique();
            $table->json('infographic_sections')->nullable();
        });

        foreach (require database_path('data/infographics.php') as $item) {
            DB::table('educational_contents')->insertOrIgnore([
                ...$item,
                'infographic_sections' => json_encode($item['infographic_sections'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'is_published' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('educational_contents', function (Blueprint $table) {
            $table->dropUnique(['infographic_key']);
            $table->dropColumn(['category', 'calendar_month', 'infographic_key', 'infographic_sections']);
        });
    }
};
