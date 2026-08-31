<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40);
            $table->string('status', 20);
            $table->string('channel', 20)->nullable();
            $table->string('title');
            $table->string('summary', 500);
            $table->string('failure_code', 60)->nullable();
            $table->text('analyst_message')->nullable();
            $table->text('resolution_steps')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->string('recipient', 255)->nullable();
            $table->unsignedSmallInteger('batch_number')->nullable();
            $table->unsignedSmallInteger('total_batches')->nullable();
            $table->foreignId('birthday_notification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mass_notification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('corporate_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('technical_detail')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['status', 'logged_at']);
            $table->index(['source', 'logged_at']);
            $table->index(['channel', 'logged_at']);
            $table->index(['failure_code', 'logged_at']);
            $table->index('mass_notification_id');
            $table->index('birthday_notification_id');
            $table->index('corporate_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatch_logs');
    }
};
