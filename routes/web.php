<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }

    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [AttendanceController::class, 'index'])->name('dashboard');
    Route::get('/home', [AttendanceController::class, 'home'])->name('home');
    Route::post('/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
    Route::get('/attendance/view/{filename}', [AttendanceController::class, 'viewUploadedFile'])
        ->where('filename', '[^/]+')
        ->name('attendance.view');
    Route::get('/attendance/departments', [AttendanceController::class, 'departments'])->name('attendance.departments');
    Route::get('/attendance/workers', [AttendanceController::class, 'workers'])->name('attendance.workers');
    Route::get('/attendance/weekly', [AttendanceController::class, 'weekly'])->name('attendance.weekly');
    Route::get('/attendance/weekly/departments', [AttendanceController::class, 'weeklyDepartments'])->name('attendance.weekly.departments');
    Route::get('/attendance/monthly', [AttendanceController::class, 'monthly'])->name('attendance.monthly');
    Route::get('/attendance/monthly/departments', [AttendanceController::class, 'monthlyDepartments'])->name('attendance.monthly.departments');
    Route::get('/attendance/quarterly', [AttendanceController::class, 'quarterly'])->name('attendance.quarterly');
    Route::get('/attendance/quarterly/departments', [AttendanceController::class, 'quarterlyDepartments'])->name('attendance.quarterly.departments');
    Route::get('/attendance/departments/export', [AttendanceController::class, 'exportDepartments'])->name('attendance.departments.export');
    Route::get('/attendance/download/{filename}', [AttendanceController::class, 'downloadUploadedFile'])
        ->where('filename', '[^/]+')
        ->name('attendance.download');
    Route::delete('/attendance/delete/{filename}', [AttendanceController::class, 'deleteUploadedFile'])
        ->where('filename', '[^/]+')
        ->name('attendance.delete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
