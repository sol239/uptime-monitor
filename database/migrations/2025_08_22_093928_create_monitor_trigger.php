<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trigger AFTER INSERT
        DB::unprepared('
            CREATE TRIGGER after_monitor_insert
            AFTER INSERT ON monitors
            FOR EACH ROW
            BEGIN
                INSERT INTO monitor_updates (monitor_id, must_update, created_at, updated_at)
                VALUES (NEW.id, TRUE, NOW(), NOW());
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_monitor_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_monitor_update');
    }
};
