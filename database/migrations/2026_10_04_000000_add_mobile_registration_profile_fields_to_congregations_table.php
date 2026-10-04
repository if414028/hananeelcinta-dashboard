<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->string('blood_type', 2)->nullable();
            $table->string('last_education', 100)->nullable();
            $table->string('baptism_church')->nullable();
            $table->boolean('holy_spirit_baptism')->nullable();
            $table->string('church_origin')->nullable();
            $table->text('reason_to_move_church')->nullable();
            $table->string('family_status', 100)->nullable();
            $table->string('wife_name')->nullable();
            $table->string('husband_name')->nullable();
            $table->json('children_names')->nullable();
            $table->json('siblings_names')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->dropColumn([
                'blood_type', 'last_education', 'baptism_church', 'holy_spirit_baptism',
                'church_origin', 'reason_to_move_church', 'family_status', 'wife_name',
                'husband_name', 'children_names', 'siblings_names',
            ]);
        });
    }
};
