<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('corporate_events', 'timezone')) {
            Schema::table('corporate_events', function (Blueprint $table) {
                $table->dropColumn('timezone');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('corporate_events', 'timezone')) {
            Schema::table('corporate_events', function (Blueprint $table) {
                $table->string('timezone')->default('America/Caracas')->after('ends_at');
            });
        }
    }
};
