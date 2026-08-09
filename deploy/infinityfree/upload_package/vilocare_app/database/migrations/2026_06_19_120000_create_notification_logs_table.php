<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);
            $table->string('category', 80);
            $table->string('status', 20)->default('sent');
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->string('provider', 50)->nullable();
            $table->text('message')->nullable();
            $table->nullableMorphs('notifiable');
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
