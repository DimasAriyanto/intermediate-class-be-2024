<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class SpotifyController extends Controller
{
    public function redirectToSpotify()
    {
        return Socialite::driver('spotify')->scopes(['user-read-private', 'playlist-modify-public', 'playlist-modify-private'])->redirect();
    }

    public function handleSpotifyCallback()
    {
        try {
            $spotifyUser = Socialite::driver('spotify')->user();

            $token = $spotifyUser->token;
            $refreshToken = $spotifyUser->refreshToken;
            $expiresIn = $spotifyUser->expiresIn;

            $user = User::updateOrCreate(
                [
                    'spotify_user_id' => $spotifyUser->id,
                ],
                [
                    'name' => $spotifyUser->name ?? $spotifyUser->nickname ?? 'Unknown User',
                    'email' => $spotifyUser->email,
                    'spotify_user_id' => $spotifyUser->id,
                    'spotify_access_token' => $token,
                    'spotify_refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                ]
            );

            Auth::login($user);

            $apiToken = $user->createToken('Spotify API Token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil!',
                'user' => $spotifyUser,
                'spotify_access_token' => $token,
                'spotify_refresh_token' => $refreshToken,
                'spotify_expires_in' => $expiresIn,
                'api_token' => $apiToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal login dengan Spotify', 'message' => $e->getMessage()], 500);
        }
    }

    public function getUserProfile()
    {
        $user = Auth::user();

        $token = $user->spotify_access_token;

        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan. Login terlebih dahulu.'], 401);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('https://api.spotify.com/v1/me');

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Gagal mendapatkan data dari Spotify'], $response->status());
        }
    }

    public function getPlaylists()
    {
        $user = Auth::user();
        $spotifyUserId = $user->spotify_user_id;
        $token = $user->spotify_access_token;

        if (!$token) {
            return response()->json(['error' => 'Token not found. Please log in.'], 401);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists");

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Failed to retrieve playlists from Spotify'], $response->status());
        }
    }

    public function createPlaylist(Request $request)
    {
        $user = Auth::user();
        $spotifyUserId = $user->spotify_user_id;
        $token = $user->spotify_access_token;

        if (!$token) {
            return response()->json(['error' => 'Token not found. Please log in.'], 401);
        }

        $playlistData = [
            'name' => $request->input('name', 'New Playlist'),
            'description' => $request->input('description', ''),
            'public' => $request->input('public', true),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", $playlistData);

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Failed to create playlist on Spotify'], $response->status());
        }
    }
}
