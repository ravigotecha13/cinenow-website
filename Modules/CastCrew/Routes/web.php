<?php

use Illuminate\Support\Facades\Route;
use Modules\CastCrew\Http\Controllers\CastCrewController;

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


Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth', 'admin']], function () {

    Route::group(['prefix' => 'castcrew', 'as' => 'castcrew.'], function () {
        Route::get('/index_list', [CastCrewController::class, 'index_list'])->name('index_list');
        Route::get('/index_data/{type}', [CastCrewController::class, 'index_data'])->name('index_data');
        Route::get('/trashed', [CastCrewController::class, 'trashed'])->name('trashed');
        Route::post('bulk-action', [CastCrewController::class, 'bulk_action'])->name('bulk_action');
        Route::post('update-status/{id}', [CastCrewController::class, 'update_status'])->name('update_status');
        Route::delete('force-delete/{id}', [CastCrewController::class, 'forceDelete'])->name('force_delete');
        Route::post('restore/{id}', [CastCrewController::class, 'restore'])->name('restore');
        Route::post('generate-bio', [CastCrewController::class, 'GenerateBio'])->name('generate-bio');
    });

    Route::get('castcrew/create/{type}', [CastCrewController::class, 'create'])->name('castcrew.create');
    Route::post('castcrew/store', [CastCrewController::class, 'store'])->name('castcrew.store');
    Route::put('castcrew/update/{id}', [CastCrewController::class, 'update'])->name('castcrew.update');
    Route::get('castcrew/{id}/edit', [CastCrewController::class, 'edit'])->name('castcrew.edit');
    Route::delete('castcrew/{id}', [CastCrewController::class, 'destroy'])->name('castcrew.destroy');
    Route::get('castcrew/{type}/export', [CastCrewController::class, 'export'])->name('castcrew.export');
    Route::get('castcrew/{type}', [CastCrewController::class, 'index'])->name('castcrew.index');
});
