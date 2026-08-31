<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mass_notifications', function (Blueprint $table) {
            $table->json('audiences')->nullable()->after('channels');
        });
    }

    public function down(): void
    {
        Schema::table('mass_notifications', function (Blueprint $table) {
            $table->dropColumn('audiences');
        });
    }
};
