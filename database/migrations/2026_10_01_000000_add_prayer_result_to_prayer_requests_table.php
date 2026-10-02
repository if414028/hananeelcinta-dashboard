<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->text('prayer_result')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->dropColumn('prayer_result');
        });
    }
};
