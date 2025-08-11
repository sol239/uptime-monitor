<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');

            $table->timestamp('started_at');

            $table->string('status'); 

            $table->unsignedInteger('response_time_ms');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_logs');
    }
};
