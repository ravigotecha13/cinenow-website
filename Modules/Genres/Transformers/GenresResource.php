<?php

namespace Modules\Genres\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Support\GenreLocale;

class GenresResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $genre = $this->resource->genre ?? $this->resource;

        $imagePath = GenreLocale::fileUrl($genre);
        if (empty($imagePath) && ! empty($this->file_url)) {
            $imagePath = $this->file_url;
        }

        return [
            'id' => $this->id ?? null,
            'name' => GenreLocale::name($genre),
            'name_en' => $genre->name_en ?? $genre->name ?? null,
            'name_ar' => $genre->name_ar ?? null,
            'genre_image' => $imagePath ? setBaseUrlWithFileName($imagePath) : null,
            'status' => $this->status ?? $genre->status ?? null,
        ];
    }
}
