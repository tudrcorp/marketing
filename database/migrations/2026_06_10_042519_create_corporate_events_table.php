<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->nullable()->unique();
            $table->string('event_type');
            $table->string('modality');
            $table->string('status')->default('draft');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone')->default('America/Caracas');
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('virtual_url')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('attachments')->nullable();
            $table->json('target_audiences');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('registrations_count')->default(0);
            $table->string('registration_url')->nullable();
            $table->timestamp('registration_deadline')->nullable();
            $table->json('promoted_channels')->nullable();
            $table->foreignId('mass_notification_id')->nullable()->constrained('mass_notifications')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
            $table->index(['event_type', 'starts_at']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_events');
    }
};
