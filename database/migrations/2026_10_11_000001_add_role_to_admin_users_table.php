<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_users', fn (Blueprint $table) => $table->string('role', 30)->default('admin')->index());
    }

    public function down(): void
    {
        Schema::table('admin_users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
