<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class SubtitleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    // $timezone = Setting::where('name', 'default_time_zone')->value('val') ?? 'UTC';

    public function toArray($request): array
    {
      
        $fileUrl = setBaseUrlSubtitleFile($this->subtitle_file);

        return [
            'id' => $this->id,
            'entertainment_id' => $this->entertainment_id,
            'type' => $this->type,
            'language' => $this->language,
            'language_code' => $this->language_code,
            'subtitle_file' => $fileUrl,
            'format' => subtitle_file_format_for_player($fileUrl),
            'is_default' => $this->is_default,
        ];
    }
}
