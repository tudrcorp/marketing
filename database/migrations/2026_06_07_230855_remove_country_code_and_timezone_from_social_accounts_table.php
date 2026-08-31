<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'timezone']);
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->char('country_code', 2)->nullable()->after('profile_url');
            $table->string('timezone')->default('America/Caracas')->after('country_code');
        });
    }
};
