<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\StudentAttendanceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('attendance')->group(
    function () {
        Route::resource('/student-attendance', StudentAttendanceController::class);
        Route::get('/student-attendance/{branch}/{class}/{section}/{date}', [StudentAttendanceController::class, 'studentAttendanceFilter'])->name('student-attendance.filter');
        Route::get('/past-attendance', [StudentAttendanceController::class, 'past'])->name('attendance.past');
        Route::get('/past-attendance/{branch}/{class}/{section}/{date}', [StudentAttendanceController::class, 'attendance'])->name('past-attendance-class');
        Route::post('/student-today-attendance', [StudentAttendanceController::class, 'markAttendance'])->name('mark-today-attendance');
    }
);