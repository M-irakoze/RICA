<?php

use App\Models\User;

test('monthly report screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/attendance/monthly');

    $response->assertStatus(200);
});

test('monthly department report screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/attendance/monthly/departments');

    $response->assertStatus(200);
});

test('monthly report chart links clicked week buckets to the weekly report route', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/attendance/monthly?date=2026-08-02');

    $response->assertStatus(200)
        ->assertSee('window.location.href =')
        ->assertSee('encodeURIComponent(selectedWeekDate)')
        ->assertSee(route('attendance.weekly'));
});
