<?php

namespace Modules\Ad\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomAdsSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $urlType = strtolower((string) $this->url_type);
        $mediaRaw = $this->media;

        $mediaOut = $mediaRaw;
        if ($urlType === 'local') {
            $mediaOut = setBaseUrlWithFileName($mediaRaw);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => strtolower((string) $this->type),
            'url_type' => $urlType,
            'placement' => $this->placement ? strtolower((string) $this->placement) : $this->placement,
            'media' => $mediaOut,
            // 'media' => $mediaUrl,
            'redirect_url' => $this->redirect_url,
            'duration' => $this->duration,
            'skip_enabled' => $this->skip_enabled,
            'skip_after' => $this->skip_after,
            'target_content_type' => $this->target_content_type,
            'target_categories' => $this->target_categories,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            // 'created_at' => $this->created_at,
        ];
    }
}
