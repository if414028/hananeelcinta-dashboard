<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('prayer_requests')->where('status', 'new')->update(['status' => 'open']);
        DB::table('prayer_requests')->where('status', 'follow_up')->update(['status' => 'in_prayer']);
        DB::table('prayer_requests')->where('status', 'answered')->update(['status' => 'closed']);

        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->string('status', 30)->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->string('status', 30)->default('new')->change();
        });

        DB::table('prayer_requests')->where('status', 'open')->update(['status' => 'new']);
    }
};
