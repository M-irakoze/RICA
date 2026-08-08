<?php

use App\Models\User;

test('workers report screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/attendance/workers?date=2026-08-02');

    $response->assertStatus(200);
});
