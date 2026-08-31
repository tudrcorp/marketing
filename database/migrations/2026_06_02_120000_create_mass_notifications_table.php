<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mass_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('copy');
            $table->json('channels');
            $table->string('content_type')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_notifications');
    }
};
