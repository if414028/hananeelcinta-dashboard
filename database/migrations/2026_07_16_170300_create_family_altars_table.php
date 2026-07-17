<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_altars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('legacy_firebase_index')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('day_of_week', 20)->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('pic_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('map_url')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_altars');
    }
};
