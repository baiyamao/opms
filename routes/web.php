<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});//->middleware(['auth', 'verified'])暂时移除首页登录

Route::get('/chat', function () {
    return Inertia::render('Chat');
})->name('chat');

Route::get('/optometry-clinic', function () {
    return Inertia::render('OptometryClinic');
})->middleware(['auth', 'verified'])->name('optometry-clinic');

Route::get('/product-sales', function () {
    return Inertia::render('ProductSales');
})->middleware(['auth', 'verified'])->name('product-sales');

Route::get('/optometry-record', function () {
    return Inertia::render('OptometryRecord');
})->middleware(['auth', 'verified'])->name('optometry-record');

Route::get('/optometry-record/add', function () {
    return Inertia::render('AddOptometryRecord');
})->middleware(['auth', 'verified'])->name('add-optometry-record');

Route::get('/growthevaluation', function () {
    return Inertia::render('Growthevaluation');
})->name('growthevaluation');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/system-accounts', function () {
    return Inertia::render('SystemAccounts');
})->middleware(['auth', 'verified'])->name('system-accounts');


require __DIR__.'/auth.php';
