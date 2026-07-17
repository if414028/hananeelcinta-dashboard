<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('filename');
            $table->string('checksum', 64)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('inserted_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('skipped_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'checksum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
