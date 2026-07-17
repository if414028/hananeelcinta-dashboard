<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->string('legacy_firebase_uid')->nullable()->unique()->after('id');
            $table->text('legacy_profile_photo_url')->nullable()->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->dropUnique(['legacy_firebase_uid']);
            $table->dropColumn(['legacy_firebase_uid', 'legacy_profile_photo_url']);
        });
    }
};
