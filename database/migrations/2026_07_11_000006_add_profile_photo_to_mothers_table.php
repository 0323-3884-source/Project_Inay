<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mothers', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('pregnancy_status');
        });
    }

    public function down(): void
    {
        Schema::table('mothers', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });
    }
};
