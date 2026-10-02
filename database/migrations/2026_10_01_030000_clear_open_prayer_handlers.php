<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('prayer_requests')->where('status', 'open')->whereNotNull('handled_by')
            ->update(['handled_by' => null, 'handled_at' => null]);
    }

    public function down(): void
    {
        // Penanggung jawab lama tidak dapat dipulihkan dengan aman.
    }
};
