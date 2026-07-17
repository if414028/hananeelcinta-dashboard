<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 30)->default('text');
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->index(['group', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
