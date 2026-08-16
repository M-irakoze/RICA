<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

it('accepts xls uploads for attendance import', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent(
        'attendance.xls',
        'not-a-real-xls-file',
        'application/octet-stream'
    );

    $response = $this->post(route('attendance.import'), [
        'attendance_file' => $file,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

it('shows the latest uploaded file on the dashboard even when no rows are imported', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent(
        'attendance.xls',
        'not-a-real-xls-file',
        'application/octet-stream'
    );

    $response = $this->post(route('attendance.import'), [
        'attendance_file' => $file,
    ]);

    $response->assertRedirect();

    $uploadedFile = session('uploaded_file');

    $this->assertNotNull($uploadedFile);
    $this->get(route('attendance.daily'))
        ->assertOk()
        ->assertSee($uploadedFile);
});
