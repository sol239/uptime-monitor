<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the trigger
        DB::unprepared("
            CREATE TRIGGER update_monitor_status
            AFTER INSERT ON monitor_logs
            FOR EACH ROW
            BEGIN
                UPDATE monitors
                SET status = NEW.status
                WHERE id = NEW.monitor_id;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS update_monitor_status');
    }
};