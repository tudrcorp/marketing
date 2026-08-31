<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mass_notifications', function (Blueprint $table) {
            $table->json('recipient_ids')->nullable()->after('audiences');
        });
    }

    public function down(): void
    {
        Schema::table('mass_notifications', function (Blueprint $table) {
            $table->dropColumn('recipient_ids');
        });
    }
};
