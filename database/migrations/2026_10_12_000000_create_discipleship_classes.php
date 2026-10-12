<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discipleship_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('teacher_name');
            $table->string('banner');
            $table->string('badge');
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->dateTime('completed_at')->nullable()->index();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('discipleship_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discipleship_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('congregation_id')->constrained()->cascadeOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['discipleship_class_id', 'congregation_id'], 'discipleship_class_member_unique');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['discipleship.view', 'discipleship.create', 'discipleship.update', 'discipleship.complete'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach (Role::query()->whereIn('name', ['Admin', 'Super Admin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('discipleship_enrollments');
        Schema::dropIfExists('discipleship_classes');
        Permission::query()->where('name', 'like', 'discipleship.%')->get()->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
