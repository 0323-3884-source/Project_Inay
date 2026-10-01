<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dswd_staff')) {
            Schema::create('dswd_staff', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('office')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('mothers') && ! Schema::hasColumn('mothers', 'municipality_city')) {
            Schema::table('mothers', function (Blueprint $table) {
                $table->string('municipality_city')->nullable()->index();
            });
        }

        if (! Schema::hasTable('dswd_evaluations')) {
            Schema::create('dswd_evaluations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dswd_staff_id')->constrained('dswd_staff')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->text('feedback')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dswd_evaluations');
        if (Schema::hasTable('mothers') && Schema::hasColumn('mothers', 'municipality_city')) {
            Schema::table('mothers', fn (Blueprint $table) => $table->dropColumn('municipality_city'));
        }
        Schema::dropIfExists('dswd_staff');
    }
};
