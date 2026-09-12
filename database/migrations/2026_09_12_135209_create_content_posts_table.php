<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('copy')->nullable();
            $table->string('status')->default('idea');
            $table->string('priority')->default('medium');
            $table->string('blocker')->default('none');
            $table->string('format')->default('static');
            $table->string('pillar')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->dateTime('timer_started_at')->nullable();
            $table->boolean('is_replicable')->default(false);
            $table->boolean('is_evergreen')->default(false);
            $table->string('reference_image')->nullable();
            $table->unsignedInteger('board_position')->default(0);
            $table->unsignedInteger('feed_position')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index(['status', 'board_position']);
            $table->index('scheduled_at');
            $table->index('blocker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_posts');
    }
};
