<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'department',
        'position',
        'gender',
        'attendance_date',
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
        'attendance_time',
        'status',
        'source',
        'records',
        'uploaded_file',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];
}
