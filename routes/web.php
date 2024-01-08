<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\ReportController;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/insert', function () {
    $user = new Admin();
    $user->name = 'Kevin Anderson';
    $user->email = 'admin@admin.com';
    $user->password = Hash::make('qweqwe');
    $user->save();
});

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', function () {
        return view('auth-login');
    })->name('login');

    Route::post('login', [AdminLoginController::class, 'login']);
});

Route::prefix('dashboard')->middleware(['auth'])->name('dashboard-')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('users', [AdminController::class, 'users'])->name('users');
    Route::get('users/export', [AdminController::class, 'exportCSV'])->name('users-export-csv');
    Route::get('verify-users', [AdminController::class, 'verifyUsers'])->name('verify-users');
    Route::get('get-verify/{id}', [AdminController::class, 'getVerify'])->name('get-verify');

    Route::prefix('report')->name('report-')->group(function () {
        Route::get('/{type}', [ReportController::class, 'report']);
        Route::get('delete/{id}', [ReportController::class, 'deleteReport'])->name('delete-report');
        Route::get('user/delete/{user_id}/{report_id}', [ReportController::class, 'deleteUser'])->name('delete-user');
        Route::get('post/delete/{post_id}/{report_id}', [ReportController::class, 'deletePost'])->name('delete-post');
        Route::get('reel/delete/{reel_id}/{report_id}', [ReportController::class, 'deleteReel'])->name('delete-reel');
        Route::get('story/delete/{story_id}/{report_id}', [ReportController::class, 'deleteStory'])->name('delete-story');
    });


    Route::get('faqs', [AdminController::class, 'faqs'])->name('faqs');
    Route::post('add-faq', [AdminController::class, 'addFaq'])->name('add-faq');
    Route::get('delete-faq/{id}', [AdminController::class, 'deleteFaq'])->name('delete-faq');


    Route::prefix('ticket')->name('ticket-')->group(function () {
        Route::get('ticket/{status}', [AdminTicketController::class, 'ticket'])->name('ticket');
        Route::get('close-ticket/{id}', [AdminTicketController::class, 'closeTicket'])->name('close-ticket');
        Route::get('messages/{from_to}', [AdminTicketController::class, 'messages'])->name('messages');
        Route::post('send-message', [AdminTicketController::class, 'sendMessage'])->name('send-message');
        Route::get('category', [AdminTicketController::class, 'getCategory'])->name('category');
        Route::get('delete-category/{id}', [AdminTicketController::class, 'deleteCategory'])->name('delete-category');
        Route::post('add-category', [AdminTicketController::class, 'addCategory'])->name('add-category');
    });










    Route::get('logout', [AdminLoginController::class, 'logout'])->name('logout');
});
