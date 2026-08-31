<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_publications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marketing_campaign_id');
        });

        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('marketing_segments');
    }

    public function down(): void
    {
        Schema::create('marketing_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('audience_type', 32);
            $table->text('description')->nullable();
            $table->json('criteria')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status', 32)->default('draft')->index();
            $table->text('objective')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('marketing_segment_id')->nullable()->constrained('marketing_segments')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('editorial_publications', function (Blueprint $table) {
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->after('social_account_id')
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
        });
    }
};
