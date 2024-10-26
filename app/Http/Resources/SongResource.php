<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'spotify_id' => $this->spotify_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'album' => $this->album,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
