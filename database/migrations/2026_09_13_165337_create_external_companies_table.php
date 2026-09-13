<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 180);
            $table->string('legal_name', 180);
            $table->string('document_id', 20)->index();
            $table->string('phone', 30);
            $table->string('email', 180);
            $table->string('responsible_name', 180);
            $table->string('responsible_document_id', 20);
            $table->string('responsible_phone', 30);
            $table->string('responsible_email', 180);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_companies');
    }
};
