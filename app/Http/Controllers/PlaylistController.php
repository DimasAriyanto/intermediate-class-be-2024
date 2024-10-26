<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PlaylistController extends Controller
{
    public function index()
    {
        $playlists = Playlist::all();

        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'Succesfully get data playlists',
            'data' => $playlists
        ]);
    }

    public function getAllPlaylistByUser()
    {
        $user = Auth::user();
        $playlists = Playlist::where('user_id', $user->id)->get();

        return response()->json($playlists);
    }

    // Create a new playlist and store it in the database
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $playlist = Playlist::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($playlist, 201);
    }

    // Display a single playlist
    public function show($id)
    {
        $playlist = Playlist::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($playlist);
    }

    // Update an existing playlist
    public function update(Request $request, $id)
    {
        $playlist = Playlist::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $playlist->update($request->only(['name', 'description']));

        return response()->json($playlist);
    }

    // Delete a playlist
    public function destroy($id)
    {
        $playlist = Playlist::where('user_id', Auth::id())->findOrFail($id);
        $playlist->delete();

        return response()->json(['message' => 'Playlist deleted successfully']);
    }

    // Push the playlist to Spotify
    public function pushToSpotify($id)
    {
        $user = Auth::user();
        $playlist = Playlist::where('user_id', $user->id)->findOrFail($id);

        // Retrieve Spotify token and user ID
        $spotifyUserId = $user->spotify_user_id;
        $token = $user->spotify_access_token;

        if (!$token) {
            return response()->json(['error' => 'Token not found. Please log in to Spotify.'], 401);
        }

        // Prepare the data for Spotify API
        $data = [
            'name' => $playlist->name,
            'description' => $playlist->description,
            'public' => true, // Set this based on your needs
        ];

        // Send POST request to Spotify API to create the playlist
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", $data);

        // Return response based on Spotify API result
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Failed to push playlist to Spotify'], $response->status());
        }
    }
}
