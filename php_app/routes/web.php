<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;

// الصفحة الرئيسية توجه إلى dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// مسار تجريبي للـ PDF
Route::get('/test-pdf/{id?}', [ContractController::class, 'pdf'])->name('test.pdf');

// Dashboard routes
Route::middleware(['web'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/manager-dashboard', [DashboardController::class, 'managerDashboard'])->name('manager.dashboard');
    
    // Auth routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register'); 
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // Password reset routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
    
    // Contract routes
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{id}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::get('/contracts-in-progress', [ContractController::class, 'inProgress'])->name('contracts.in-progress');
    Route::get('/contracts-closed', [ContractController::class, 'closed'])->name('contracts.closed');
    
    // Manager actions
    Route::post('/manager/approve/{id}', [ContractController::class, 'approve'])->name('manager.approve');
    Route::post('/manager/reject/{id}', [ContractController::class, 'reject'])->name('manager.reject');
    Route::post('/contracts/{id}/archive', [ContractController::class, 'archive'])->name('contracts.archive');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
    
    // Tasks
    Route::post('/tasks/{id}/update', [DashboardController::class, 'updateTask'])->name('tasks.update');
    Route::delete('/tasks/{id}', [DashboardController::class, 'deleteTask'])->name('tasks.delete');
});
