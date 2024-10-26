<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Playlist;
use Illuminate\Http\Request;
use App\Http\Resources\SongResource;
use Illuminate\Support\Facades\Http;

class SongController extends Controller
{
    public function index()
    {
        $songs = Song::all();
        return SongResource::collection($songs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'spotify_id' => 'nullable|string|unique:songs,spotify_id',
            'title' => 'required_without:spotify_id|string',
            'artist' => 'required_without:spotify_id|string',
            'album' => 'required_without:spotify_id|string',
        ]);

        if ($request->spotify_id) {
            $spotifyResponse = Http::withToken($request->user()->spotify_access_token)
                ->get("https://api.spotify.com/v1/tracks/{$request->spotify_id}");

            if (!$spotifyResponse->successful()) {
                return response()->json(['error' => 'Failed to retrieve song from Spotify'], 404);
            }

            $spotifyData = $spotifyResponse->json();

            $song = Song::create([
                'spotify_id' => $spotifyData['id'],
                'title' => $spotifyData['name'],
                'artist' => $spotifyData['artists'][0]['name'],
                'album' => $spotifyData['album']['name'],
            ]);
        } else {
            // Jika spotify_id tidak tersedia, masukkan data lagu secara manual
            $song = Song::create([
                'title' => $request->title,
                'artist' => $request->artist,
                'album' => $request->album,
            ]);
        }

        return new SongResource($song);
    }

    public function show($id)
    {
        $song = Song::findOrFail($id);
        return new SongResource($song);
    }

    public function update(Request $request, $id)
    {
        $song = Song::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string',
            'artist' => 'sometimes|string',
            'album' => 'sometimes|string',
        ]);

        $song->update($request->only(['title', 'artist', 'album']));

        return new SongResource($song);
    }

    public function destroy($id)
    {
        $song = Song::findOrFail($id);
        $song->delete();

        return response()->json(['message' => 'Song deleted successfully'], 200);
    }

    public function addSongToPlaylist(Request $request, $playlistId, $songId)
    {
        $playlist = Playlist::findOrFail($playlistId);
        $song = Song::findOrFail($songId);

        if (!$playlist->songs()->where('song_id', $songId)->exists()) {
            $playlist->songs()->attach($songId);
            return response()->json(['message' => 'Song added to playlist successfully'], 200);
        } else {
            return response()->json(['message' => 'Song already exists in playlist'], 400);
        }
    }
}
