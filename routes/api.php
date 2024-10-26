<?php

use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SpotifyController;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return response()->json([
        'status' => 200,
        'success' => true,
        'message' => 'Welcome to API SpotPlay',
    ]);
});

Route::middleware([StartSession::class])->group(function () {
    Route::get('/login/spotify', [SpotifyController::class, 'redirectToSpotify'])->withoutMiddleware('auth.check');
    Route::get('/callback/spotify', [SpotifyController::class, 'handleSpotifyCallback'])->withoutMiddleware('auth.check');
});

Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {
    Route::get('/spotify/user-profile', [SpotifyController::class, 'getUserProfile']);
    Route::get('/spotify/playlists', [SpotifyController::class, 'getPlaylists']);
    Route::post('/spotify/playlists', [SpotifyController::class, 'createPlaylist']);

    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::post('/playlists', [PlaylistController::class, 'store']);
    Route::get('/playlists/{id}', [PlaylistController::class, 'show']);
    Route::put('/playlists/{id}', [PlaylistController::class, 'update']);
    Route::delete('/playlists/{id}', [PlaylistController::class, 'destroy']);
    Route::post('/playlists/{id}/push', [PlaylistController::class, 'pushToSpotify']);

    Route::get('/songs', [SongController::class, 'index']);
    Route::post('/songs', [SongController::class, 'store']);
    Route::get('/songs/{id}', [SongController::class, 'show']);
    Route::put('/songs/{id}', [SongController::class, 'update']);
    Route::delete('/songs/{id}', [SongController::class, 'destroy']);

    Route::post('/playlists/{playlistId}/songs/{songId}', [SongController::class, 'addSongToPlaylist']);
});
