<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $t) {
                $t->id();
                $t->string('first_name', 100);
                $t->string('last_name', 100);
                $t->string('email', 150)->unique();
                $t->string('phone', 30)->nullable();
                $t->string('password');
                $t->enum('role', ['admin', 'cashier'])->default('cashier');
                $t->string('status', 20)->default('active');
                $t->rememberToken();
                $t->string('reset_token', 128)->nullable();
                $t->dateTime('token_expires_at')->nullable();
                $t->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $t) {
                if (! Schema::hasColumn('users', 'status')) {
                    $t->string('status', 20)->default('active');
                } if (! Schema::hasColumn('users', 'remember_token')) {
                    $t->rememberToken();
                }
            });
        }
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $t) {
                $t->string('id')->primary();
                $t->foreignId('user_id')->nullable()->index();
                $t->string('ip_address', 45)->nullable();
                $t->text('user_agent')->nullable();
                $t->longText('payload');
                $t->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
