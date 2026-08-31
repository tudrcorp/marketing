<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_publications', function (Blueprint $table) {
            $table->string('reference_image')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_publications', function (Blueprint $table) {
            $table->dropColumn('reference_image');
        });
    }
};
