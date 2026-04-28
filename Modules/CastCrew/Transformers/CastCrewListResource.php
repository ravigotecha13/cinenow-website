<?php

namespace Modules\CastCrew\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CastCrew\Support\CastCrewLocale;

class CastCrewListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */



    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'name' => CastCrewLocale::name($this->resource),
            'name_en' => $this->name_en ?? $this->name,
            'name_ar' => $this->name_ar,
            'type' => 'castcrew',
            'bio' => CastCrewLocale::bio($this->resource),
            'place_of_birth' => CastCrewLocale::placeOfBirth($this->resource),
            'dob' => $this->dob,
            'designation' => CastCrewLocale::designation($this->resource),
            'profile_image' => setBaseUrlWithFileName($this->file_url),
        ];
    }
}
