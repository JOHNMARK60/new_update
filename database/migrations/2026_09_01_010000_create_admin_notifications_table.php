<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50);
                $table->string('title', 160);
                $table->string('body');
                $table->string('link_url')->nullable();
                $table->string('related_type', 80)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->dateTime('read_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['related_type', 'related_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
