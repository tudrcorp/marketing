<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('social_accounts')
            ->whereIn('platform', ['linkedin', 'whatsapp', 'other'])
            ->update(['platform' => 'instagram']);
    }

    public function down(): void
    {
        //
    }
};
