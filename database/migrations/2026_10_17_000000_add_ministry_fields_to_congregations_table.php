<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->boolean('is_serving')->default(false);
            $table->string('ministry_unit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->dropColumn(['is_serving', 'ministry_unit']);
        });
    }
};
