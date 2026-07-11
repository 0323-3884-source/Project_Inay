<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mothers', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('pregnancy_status')->nullable();
            $table->decimal('location_latitude', 10, 7)->nullable();
            $table->decimal('location_longitude', 10, 7)->nullable();
            $table->unsignedInteger('location_accuracy')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mothers', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'blood_type',
                'pregnancy_status',
                'location_latitude',
                'location_longitude',
                'location_accuracy',
            ]);
        });
    }
};
