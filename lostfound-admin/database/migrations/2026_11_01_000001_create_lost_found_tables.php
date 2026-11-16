<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->enum('role', ['admin', 'user'])->default('user');
            $t->string('student_id', 40)->nullable();
            $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('description', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['lost', 'found']);
            $t->string('name', 150);
            $t->text('description');
            $t->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $t->string('location', 150);
            $t->date('date_event');
            $t->enum('status', ['pending', 'listed', 'claimed', 'returned', 'rejected'])->default('pending');
            $t->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('photo_path', 80)->nullable();
            $t->text('admin_note')->nullable();
            $t->timestamps();
            $t->index(['type', 'status']);
        });

        Schema::create('claims', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('proof_of_ownership');
            $t->text('identifying_details')->nullable();
            $t->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $t->text('admin_remarks')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['item_id', 'user_id']);
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 60)->index();
            $t->text('details')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('api_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->char('token_hash', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['api_tokens', 'activity_logs', 'claims', 'items', 'categories', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
