<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congregations', function (Blueprint $table): void {
            $table->id();
            $table->string('member_number')->unique();
            $table->string('full_name')->index();
            $table->string('nickname')->nullable();
            $table->string('gender', 20)->index();
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable()->index();
            $table->string('marital_status', 20)->nullable();
            $table->string('phone_number', 30)->nullable()->index();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('occupation')->nullable();
            $table->string('baptism_status', 30)->default('unknown');
            $table->date('baptism_date')->nullable();
            $table->string('membership_status', 20)->default('visitor')->index();
            $table->date('joined_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('profile_photo')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congregations');
    }
};
