<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_event_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('audience_source')->nullable();
            $table->string('status')->default('registered');
            $table->string('source')->default('manual');
            $table->foreignId('registered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->unique(['corporate_event_id', 'email']);
            $table->index(['corporate_event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_event_registrations');
    }
};
