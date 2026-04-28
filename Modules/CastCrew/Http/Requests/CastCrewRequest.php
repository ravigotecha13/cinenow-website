<?php

namespace Modules\CastCrew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;


class CastCrewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'bio_en' => ['required', 'string'],
            'bio_ar' => ['required', 'string'],
            'place_of_birth_en' => ['required', 'string', 'max:255'],
            'place_of_birth_ar' => ['required', 'string', 'max:255'],
            'designation_en' => ['nullable', 'string', 'max:255'],
            'designation_ar' => ['nullable', 'string', 'max:255'],
            'type' => ['required'],
            'dob' => ['required'],
        ];

        return $rules;
        
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function messages()
    {
        return [
            'name_en.required' => 'English name is required.',
            'name_ar.required' => 'Arabic name is required.',
            'bio_en.required' => 'English bio is required.',
            'bio_ar.required' => 'Arabic bio is required.',
            'place_of_birth_en.required' => 'English place of birth is required.',
            'place_of_birth_ar.required' => 'Arabic place of birth is required.',
            'type.required' => 'Type is required.',
            'dob.required' => 'Date of birth is required.',
        ];
    }
}
