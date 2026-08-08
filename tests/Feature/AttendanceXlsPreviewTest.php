<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('can preview an uploaded xls file', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $samplePath = base_path('vendor/shuchkin/simplexls/examples/books.xls');
    $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'books_upload.xls';
    copy($samplePath, $tempPath);

    $file = new UploadedFile(
        $tempPath,
        'books.xls',
        'application/vnd.ms-excel',
        null,
        true
    );

    $response = $this->post(route('attendance.import'), [
        'attendance_file' => $file,
    ]);

    $response->assertRedirect();

    $uploadedFilename = session('uploaded_file');
    $this->assertNotNull($uploadedFilename);

    $viewResponse = $this->get(route('attendance.view', $uploadedFilename));
    $viewResponse->assertOk();
    $viewResponse->assertSee('Format: XLS');
    $viewResponse->assertDontSee('Preview is unavailable for this file type. Please download it to view the contents.');

    unlink($tempPath);
});
