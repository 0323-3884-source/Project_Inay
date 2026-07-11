<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('offer_type', 16)->nullable()->after('status');
            $table->longText('offer_sdp')->nullable()->after('offer_type');
            $table->string('answer_type', 16)->nullable()->after('offer_sdp');
            $table->longText('answer_sdp')->nullable()->after('answer_type');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['offer_type', 'offer_sdp', 'answer_type', 'answer_sdp']);
        });
    }
};
