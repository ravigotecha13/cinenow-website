<?php

namespace Modules\CastCrew\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CastCrew\Support\CastCrewAutoTranslator;

class CastCrewListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */



    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'name' => CastCrewAutoTranslator::translate($this->name),
            'type' => 'castcrew',
            'bio' => CastCrewAutoTranslator::translate($this->bio),
            'place_of_birth' => CastCrewAutoTranslator::translate($this->place_of_birth),
            'dob' => $this->dob,
            'designation' => CastCrewAutoTranslator::translate($this->designation),
            'profile_image' => setBaseUrlWithFileName($this->file_url),
        ];
    }
}
