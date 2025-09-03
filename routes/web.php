<?php

use App\Http\Controllers\AdminController;
//use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
    return redirect()->route('login');
});

Route::middleware(['auth', 'update_user_last_activity'])->group(function () {
    Route::get('/pl', [InscriptionController::class, 'summary'])->name('pl');
    Route::get('/pl/data', [InscriptionController::class, 'data'])->name('pl.data');
    Route::post('/pl/tsv', [InscriptionController::class, 'downloadTSV'])->name('pl.tsv');

    Route::get('/assets', [InscriptionController::class, 'assets'])->name('assets');
    Route::get('/assets/data', [InscriptionController::class, 'assetData'])->name('assets.data');
});

Route::middleware(['auth', 'update_user_last_activity'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/wallets', [ProfileController::class, 'getWallets'])->name('profile.getWallets');
    Route::post('/profile/wallets', [ProfileController::class, 'addWallet'])->name('profile.addWallet');
    Route::delete('/profile/wallets/{wallet}', [ProfileController::class, 'deleteWallet'])->name('profile.deleteWallet');
    Route::get('/profile/licenses', [ProfileController::class, 'getLicenses'])->name('profile.getLicenses');
    Route::post('/profile/licenses', [ProfileController::class, 'addLicense'])->name('profile.addLicense');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/users/{id}/admin-status', [AdminController::class, 'updateAdminStatus'])->name('admin.updateAdminStatus');
    Route::post('/admin/users/{id}/add-license', [AdminController::class, 'addUserLicense'])->name('admin.addUserLicense');
    Route::post('/admin/users/{id}/remove-license', [AdminController::class, 'removeUserLicense'])->name('admin.removeUserLicense');
    Route::post('/admin/licenses', [AdminController::class, 'createLicense'])->name('admin.createLicense');
    Route::delete('/admin/licenses/{id}', [AdminController::class, 'deleteLicense'])->name('admin.deleteLicense');
    Route::get('/admin/licenses/{id}/keys', [AdminController::class, 'downloadLicenseKeysCSV'])->name('admin.downloadLicenseKeys');
    Route::delete('/admin/user/{id}', [AdminController::class, 'deleteUser'])->name('admin.deleteUser');
    Route::post('/admin/user/{id}/password', [AdminController::class, 'updateUserPassword'])->name('admin.updateUserPassword');
    Route::post('/admin/user/{id}/impersonate', [AdminController::class, 'impersonateUser'])->name('admin.impersonateUser');
});

require __DIR__.'/auth.php';
