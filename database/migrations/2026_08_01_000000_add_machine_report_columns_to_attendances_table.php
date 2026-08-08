<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('department')->nullable()->after('name');
            $table->string('position')->nullable()->after('department');
            $table->string('gender')->nullable()->after('position');
            $table->string('week')->nullable()->after('attendance_date');
            $table->string('timetable')->nullable()->after('week');
            $table->time('check_in')->nullable()->after('timetable');
            $table->time('check_out')->nullable()->after('check_in');
            $table->integer('work_minutes')->nullable()->after('check_out');
            $table->integer('ot_minutes')->nullable()->after('work_minutes');
            $table->integer('attended_minutes')->nullable()->after('ot_minutes');
            $table->integer('late_minutes')->nullable()->after('attended_minutes');
            $table->integer('early_minutes')->nullable()->after('late_minutes');
            $table->integer('absent_minutes')->nullable()->after('early_minutes');
            $table->integer('leave_minutes')->nullable()->after('absent_minutes');
            $table->text('records')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'position',
                'gender',
                'week',
                'timetable',
                'check_in',
                'check_out',
                'work_minutes',
                'ot_minutes',
                'attended_minutes',
                'late_minutes',
                'early_minutes',
                'absent_minutes',
                'leave_minutes',
                'records',
            ]);
        });
    }
};
