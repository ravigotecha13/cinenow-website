<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\DashboardController;
use Modules\Frontend\Http\Controllers\PerviewPaymentController;
use Modules\Frontend\Http\Controllers\API\TransactionController;
use Modules\Frontend\Http\Controllers\TvShowController;
use Modules\Frontend\Http\Controllers\PpvWatchController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/ 


Route::get('top-10-movie', [DashboardController::class, 'Top10Movies']);
Route::get('latest-movie', [DashboardController::class, 'LatestMovies']);
Route::get('fetch-languages', [DashboardController::class, 'FetchLanguages']);
Route::get('popular-movie', [DashboardController::class, 'PopularMovies']);
Route::get('top-channels', [DashboardController::class, 'TopChannels']);
Route::get('popular-tvshows', [DashboardController::class, 'PopularTVshows']);
Route::get('favorite-personality', [DashboardController::class, 'favoritePersonality']);
Route::get('free-movie', [DashboardController::class, 'FreeMovies']);
Route::get('get-gener', [DashboardController::class, 'GetGener']);
Route::get('get-video', [DashboardController::class, 'GetVideo']);
Route::get('base-on-last-watch-movie', [DashboardController::class, 'GetLastWatchContent']);
Route::get('most-like-movie', [DashboardController::class, 'MostLikeMoive']);
Route::get('most-view-movie', [DashboardController::class, 'MostviewMoive']);
Route::get('country-tranding-movie', [DashboardController::class, 'TrandingInCountry']);
Route::get('favorite-genres', [DashboardController::class, 'FavoriteGenres']);
Route::get('user-favorite-personality', [DashboardController::class, 'UserfavoritePersonality']);
Route::get('pay-per-view', [DashboardController::class, 'payperview']);
Route::get('movies-pay-per-view', [DashboardController::class, 'moviePayperview']);
Route::get('tvshows-pay-per-view', [DashboardController::class, 'tvShowPayperview']);
Route::get('videos-pay-per-view', [DashboardController::class, 'videosPayperview']);
Route::get('sessions-pay-per-view', [DashboardController::class, 'getSessionsPayPerView']);
Route::get('episodes-pay-per-view', [DashboardController::class, 'getEpisodesPayPerView']);

Route::get('pay-per-view-list', [PerviewPaymentController::class, 'PayPerViewList']);


Route::get('web-continuewatch-list', [DashboardController::class, 'ContinuewatchList']);

Route::get('get-pinpopup/{id}', [DashboardController::class, 'getPinpopup']);

Route::get('v2/web-continuewatch-list', [DashboardController::class, 'ContinuewatchListV2']);
Route::get('v2/top-10-movie', [DashboardController::class, 'Top10MoviesV2']);

Route::post('save-payment-pay-per-view', [PerviewPaymentController::class, 'savePaymentPayperview']);
Route::post('start-date', [PerviewPaymentController::class, 'setStartDate']);
Route::get('/transaction-history', [TransactionController::class, 'transactionHistory']);

Route::post('ppv/check-access', [PpvWatchController::class, 'checkAccess']);
Route::post('ppv/save-progress', [PpvWatchController::class, 'saveProgress']);
Route::post('ppv/consume-ticket', [PpvWatchController::class, 'consumeTicket']);

Route::get('/check-episode-purchase', [TvShowController::class, 'checkEpisodePurchase'])->name('check.episode.purchase');
Route::get('/check-movie-purchase', [TvShowController::class, 'checkMoviePurchase'])->name('check.movie.purchase');










