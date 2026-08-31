<?php

use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventRegistrationUrlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('corporate_events', 'registration_token')) {
            Schema::table('corporate_events', function (Blueprint $table) {
                $table->string('registration_token', 64)->nullable()->unique()->after('registration_url');
            });
        }

        if (! Schema::hasColumn('corporate_event_registrations', 'document_id')) {
            Schema::table('corporate_event_registrations', function (Blueprint $table) {
                $table->string('document_id', 30)->nullable()->after('full_name');
                $table->unique(['corporate_event_id', 'document_id'], 'corp_event_regs_event_document_unique');
            });
        }

        $urlService = app(CorporateEventRegistrationUrlService::class);

        CorporateEvent::query()
            ->whereNull('registration_token')
            ->eachById(function (CorporateEvent $event) use ($urlService): void {
                $token = $urlService->generateToken();

                $event->update([
                    'registration_token' => $token,
                    'registration_url' => $urlService->buildUrl($token),
                ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('corporate_event_registrations', 'document_id')) {
            Schema::table('corporate_event_registrations', function (Blueprint $table) {
                $table->dropUnique('corp_event_regs_event_document_unique');
                $table->dropColumn('document_id');
            });
        }

        if (Schema::hasColumn('corporate_events', 'registration_token')) {
            Schema::table('corporate_events', function (Blueprint $table) {
                $table->dropColumn('registration_token');
            });
        }
    }
};
