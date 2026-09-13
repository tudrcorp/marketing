<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_companies', function (Blueprint $table) {
            $table->string('type', 20)->default('company')->after('id')->index();

            // Una persona natural no tiene razón social y su responsable es opcional.
            $table->string('legal_name', 180)->nullable()->change();
            $table->string('responsible_name', 180)->nullable()->change();
            $table->string('responsible_document_id', 20)->nullable()->change();
            $table->string('responsible_phone', 30)->nullable()->change();
            $table->string('responsible_email', 180)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('external_companies', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');

            $table->string('legal_name', 180)->nullable(false)->change();
            $table->string('responsible_name', 180)->nullable(false)->change();
            $table->string('responsible_document_id', 20)->nullable(false)->change();
            $table->string('responsible_phone', 30)->nullable(false)->change();
            $table->string('responsible_email', 180)->nullable(false)->change();
        });
    }
};
