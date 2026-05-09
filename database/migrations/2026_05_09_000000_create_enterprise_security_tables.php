<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('enterprise_access_controls', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64);
            $table->string('area', 40);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'area']);
            $table->index(['area', 'locked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_access_controls');
        Schema::dropIfExists('system_settings');
    }
};
