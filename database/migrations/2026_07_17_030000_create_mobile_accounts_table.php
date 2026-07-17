<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('congregation_id')->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('firebase_uid', 128)->unique();
            $table->string('email')->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->json('provider_ids')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_authenticated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('last_platform', 20)->nullable();
            $table->string('last_app_version', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_accounts');
    }
};
