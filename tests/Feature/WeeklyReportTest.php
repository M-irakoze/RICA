<?php

use App\Models\User;

test('weekly report chart links clicked days to the daily report route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/attendance/weekly?date=2026-08-02');

    $response->assertStatus(200)
        ->assertSee('window.location.href =')
        ->assertSee('encodeURIComponent(selectedDate)')
        ->assertSee(route('attendance.daily'));
});
