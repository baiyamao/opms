<?php

use App\Http\Controllers\FindHuZhouFuYouRecordByRegisterRecordController;
use App\Http\Controllers\FindOptometryRecordByRegisterRecordController;
use App\Http\Controllers\OptometryRecordController;
use App\Http\Controllers\SystemAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GrowthEvaluationController;
use App\Http\Controllers\API\AuthController;

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

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Route::middleware('auth:sanctum')->post('/evaluate-growth', [GrowthEvaluationController::class, 'evaluate']);
Route::post('/evaluate-growth', [GrowthEvaluationController::class, 'evaluate']);

Route::apiResource('system-accounts', SystemAccountController::class);
// 创建视光档案的资源路由
Route::resource('optometry-records', OptometryRecordController::class);

Route::post('/wdhis-login', [FindOptometryRecordByRegisterRecordController::class, 'wdhisLogin']);
Route::post('/get-register-list-with-optometry-record', [FindOptometryRecordByRegisterRecordController::class, 'getRegisterListWithOption']);
Route::post('/get-system-account', [FindHuZhouFuYouRecordByRegisterRecordController::class, 'systemAccount']);
Route::post('/get-hu-zhou-fu-you-record', [FindHuZhouFuYouRecordByRegisterRecordController::class, 'findProfileWithInfo']);

