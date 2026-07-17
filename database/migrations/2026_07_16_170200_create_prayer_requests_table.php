<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('prayer_category', 30)->index();
            $table->text('prayer_content');
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_confidential')->default(true)->index();
            $table->string('status', 30)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('source', 20)->default('website')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_requests');
    }
};
