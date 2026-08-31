<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('media_urls')->nullable();
            $table->json('hashtags')->nullable();
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('published_at')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['social_account_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_publications');
    }
};
