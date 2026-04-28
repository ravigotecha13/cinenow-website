<?php

namespace Modules\Genres\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class GenresRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_en' => 'required|string',
            'description_ar' => 'nullable|string',
            'status' => 'sometimes|boolean',
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
            'name_en.string' => 'English name must be a string.',
            'name_en.max' => 'English name cannot exceed 255 characters.',
            'name_ar.string' => 'Arabic name must be a string.',
            'name_ar.max' => 'Arabic name cannot exceed 255 characters.',
            'description_en.required' => 'English description is required.',
            'description_en.string' => 'English description must be a string.',
            'description_ar.string' => 'Arabic description must be a string.',
            'status.boolean' => 'Status must be true or false.',
        ];
    }
}
