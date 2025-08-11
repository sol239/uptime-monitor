<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('label');
            $table->integer('periodicity'); // allowed range: 5-300 seconds
            $table->string('type')->nullable();
            $table->string('badge_label')->nullable();
            $table->string('status')->default('unknown');
            $table->string('latest_status')->nullable(); // Result: succeeded or failed
            
            // Ping monitor fields
            $table->string('hostname')->nullable(); // Host name or IP address
            $table->integer('port')->nullable(); // Port to connect to
            
            // Website monitor fields
            $table->text('url')->nullable(); // URL to connect to
            $table->boolean('check_status')->default(false); // Check HTTP status
            $table->json('keywords')->nullable(); // Keywords to check in response
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
