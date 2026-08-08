<?php

use App\Models\Attendance;
use App\Models\User;

it('shows only the selected attendance status rows on the daily dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Attendance::create([
        'employee_id' => '1001',
        'name' => 'Alice',
        'department' => 'RICA/Finance Office',
        'position' => 'Staff',
        'attendance_date' => '2026-08-01',
        'check_in' => '08:00:00',
        'check_out' => '17:00:00',
        'status' => 'present',
        'work_minutes' => 540,
    ]);

    Attendance::create([
        'employee_id' => '1002',
        'name' => 'Bob',
        'department' => 'RICA/Finance Office',
        'position' => 'Staff',
        'attendance_date' => '2026-08-01',
        'check_in' => null,
        'check_out' => null,
        'status' => 'absent',
        'work_minutes' => 0,
    ]);

    $response = $this->get('/dashboard?date=2026-08-01&status=absent');

    $response->assertOk();
    $response->assertSee('Bob');
    $response->assertDontSee('Alice');
    $response->assertSee('Absent');
});

it('shows all total records when the total card is used on the daily dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Attendance::create([
        'employee_id' => '1001',
        'name' => 'Alice',
        'department' => 'RICA/Finance Office',
        'position' => 'Staff',
        'gender' => 'Female',
        'attendance_date' => '2026-08-01',
        'week' => 'Week 1',
        'timetable' => 'AM',
        'check_in' => '08:00:00',
        'check_out' => '17:00:00',
        'work_minutes' => 540,
        'ot_minutes' => 15,
        'attended_minutes' => 540,
        'late_minutes' => 0,
        'early_minutes' => 0,
        'absent_minutes' => 0,
        'leave_minutes' => 0,
        'attendance_time' => '08:00:00',
        'status' => 'present',
        'source' => 'Machine 01',
        'records' => '1',
        'uploaded_file' => 'attendance.xlsx',
    ]);

    Attendance::create([
        'employee_id' => '1002',
        'name' => 'Bob',
        'department' => 'RICA/Finance Office',
        'position' => 'Staff',
        'gender' => 'Male',
        'attendance_date' => '2026-08-01',
        'week' => 'Week 1',
        'timetable' => 'PM',
        'check_in' => null,
        'check_out' => null,
        'work_minutes' => 0,
        'ot_minutes' => 0,
        'attended_minutes' => 0,
        'late_minutes' => 0,
        'early_minutes' => 0,
        'absent_minutes' => 480,
        'leave_minutes' => 0,
        'attendance_time' => null,
        'status' => 'absent',
        'source' => 'Machine 02',
        'records' => '1',
        'uploaded_file' => 'attendance.xlsx',
    ]);

    $response = $this->get('/dashboard?date=2026-08-01&status=all');

    $response->assertOk();
    $response->assertSee('Alice');
    $response->assertSee('Bob');
    $response->assertSee('All records');
    $response->assertSee('Check-in');
    $response->assertSee('Check-out');
    $response->assertSee('Work minutes');
    $response->assertSee('Source');
    $response->assertSee('Records');
});
