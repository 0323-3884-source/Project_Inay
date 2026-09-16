<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_vital_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('measurement');
            $table->string('test_type')->nullable();
            $table->string('threshold_type');
            $table->string('label');
            $table->decimal('value', 8, 2)->nullable();
            $table->string('unit', 32)->nullable();
            $table->string('guideline_name');
            $table->string('guideline_version');
            $table->string('source_url', 1000)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['measurement', 'test_type']);
        });

        $now = now();
        $thresholds = collect(config('maternal_vitals.thresholds', []))
            ->map(fn (array $threshold): array => [
                ...$threshold,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($thresholds !== []) {
            DB::table('maternal_vital_thresholds')->insert($thresholds);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_vital_thresholds');
    }
};
