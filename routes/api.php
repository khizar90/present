<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('user/verify' ,[ AuthController::class, 'userVerify']);
Route::post('register/otpVerify' ,[ AuthController::class, 'otpVerify']);
Route::post('register' ,[ AuthController::class, 'register']);
Route::post('login' ,[ AuthController::class, 'login']);
Route::post('recover/verify' , [AuthController::class , 'recover']);
Route::post('recover/otpVerify' , [AuthController::class , 'recoverVerify']);
Route::post('newPassword' , [AuthController::class , 'newPassword']);
Route::post('change/password' , [AuthController::class , 'changePassword']);
Route::post('user/logout' ,[ AuthController::class, 'logout']);
Route::post('user/delete' ,[ AuthController::class, 'deleteAccount']);
Route::post('social/login' , [AuthController::class , 'socialLogin']);
Route::post('social/connect' , [AuthController::class , 'socialConnect']);
Route::post('social/remove' , [AuthController::class , 'removeSocial']);
Route::get('social/accounts/{id}' , [AuthController::class , 'getSocial']);
Route::post('edit/image' , [AuthController::class , 'editImage']);
Route::get('remove/image/{id}/{type}' , [AuthController::class , 'removeImage']);
Route::post('get/verify' , [AuthController::class , 'getVerify']);
Route::get('blocklist/{id}', [AuthController::class, 'blockList']);
Route::get('list/users/{id}', [AuthController::class, 'listUser']);
Route::post('edit/profile', [AuthController::class, 'editProfile']);
Route::get('profile/private/{id}/{status}', [AuthController::class, 'makePrivate']);
Route::get('profile/counter/{id}/{status}', [AuthController::class, 'hideCounter']);



Route::post('user/profile', [UserController::class, 'profile']);
Route::post('follow/user' , [UserController::class, 'follow']);
Route::get('followers/{id}' , [UserController::class, 'followers']);
Route::get('following/{id}' , [UserController::class, 'following']);
Route::get('remove/follower/{user_id}/{followerId}' , [UserController::class, 'removeFollwer']);
Route::post('block/user', [UserController::class, 'block']);
Route::get('friends/{id}', [UserController::class, 'friends']);
Route::post('reels', [UserController::class, 'reels']);
Route::post('home', [UserController::class, 'home']);
Route::post('notification', [UserController::class, 'notification']);
Route::get('notification/read/{id}', [UserController::class, 'notificationRead']);

Route::post('search/user' , [UserController::class, 'serachUser']);
Route::get('counter/{id}' , [UserController::class , 'counter']);
Route::post('report', [UserController::class, 'report']);




Route::post('add/ticket' , [TicketController::class , 'addTicket']);
Route::get('close/ticket/{ticket_id}' , [TicketController::class , 'closeTicket']);
Route::get('conversation/{id}' , [TicketController::class , 'conversation']);
Route::get('ticket/list/{id}/{status}' , [TicketController::class , 'list']);


Route::post('send/message' , [MessageController::class , 'sendMessage']);
Route::get('inbox/{id}' , [MessageController::class , 'inbox']);
Route::post('list/message' , [MessageController::class , 'conversation']);
Route::post('read/message' , [MessageController::class , 'readMessage']);


Route::get('splash' , [SettingController::class , 'splash']);
Route::get('faqs' , [SettingController::class , 'faqs']);
Route::get('user/{id}' ,[ SettingController::class, 'user']);


Route::post('add/post', [PostController::class, 'add']);
Route::post('comment/edit', [PostController::class, 'commentEdit']);
Route::post('edit/post', [PostController::class, 'edit']);
Route::get('delete/post/{id}', [PostController::class, 'delete']);
Route::post('like/post', [PostController::class, 'like']);
Route::get('like/list/{user_id}/{post_id}', [PostController::class, 'likeList']);
Route::post('comment', [PostController::class, 'comment']);
Route::get('comment/list/{user_id}/{post_id}', [PostController::class, 'commentList']);
Route::post('save/post', [PostController::class, 'savePost']);
Route::post('detail/post', [PostController::class, 'detailPost']);
Route::get('comment/like/{user_id}/{comment_id}', [PostController::class, 'commentLike']);
Route::get('comment/replies/{user_id}/{comment_id}', [PostController::class, 'commentReplies']);
Route::get('comment/likes/list/{user_id}/{comment_id}', [PostController::class, 'commentLikeList']);
Route::get('comment/delete/{id}', [PostController::class, 'deleteComment']);


Route::post('add/story', [StoryController::class, 'add']);
Route::get('delete/story/{id}', [StoryController::class, 'delete']);
Route::post('like/story', [StoryController::class, 'like']);
Route::get('story/like/list/{user_id}/{story_id}', [StoryController::class, 'likeList']);
Route::get('view/story/{user_id}/{story_id}', [StoryController::class, 'view']);
Route::get('view/list/{user_id}/{story_id}', [StoryController::class, 'viewList']);

