<?php

namespace Modules\Entertainment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntertainmentRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $nameEn = $this->input('name_en');
        $descriptionEn = $this->input('description_en');

        $this->merge([
            'name' => $nameEn ?: $this->input('name'),
            'description' => $descriptionEn ?: $this->input('description'),
        ]);
    }

    public function rules()
    {
        $id = request()->id;
        $rules = [
            'name' => ['required'],
            'name_en' => ['required', 'string'],
            'name_ar' => ['required', 'string'],
            'movie_access' => 'required',
            'language' => ['required'],
            'genres' => ['required'],
            'content_rating' => 'required|string',
            'actors' => ['required'],
            'directors' => ['required'],
            'IMDb_rating' => 'required|numeric|min:1|max:10',
            'duration' => ['required'],
            // 'release_date' => ['required'],
            'description' => ['required', 'string'],
            'description_en' => ['required', 'string'],
            'description_ar' => ['required', 'string'],
        ];

        $movieAccess = $this->input('movie_access');

        if ($movieAccess === 'paid') {
            $rules['plan_id'] = 'required';
        } elseif ($movieAccess === 'pay-per-view') {
            $rules['price'] = 'required|numeric';
            // $rules['discount'] = 'numeric|min:1|max:99';
            // $rules['access_duration'] = 'required|integer|min:1';
            $rules['available_for'] = 'required|integer|min:1';
        }

        if ($this->input('type') == 'movie') {
            $rules['duration'] = 'required';
            $rules['video_upload_type'] = 'required';
        }

        if ($this->has('enable_seo') && $this->enable_seo == 1) {
            $rules = array_merge($rules, [
                'meta_title' => [
                        'required',
                        'string',
                        'max:100',
                        Rule::unique('entertainments', 'meta_title')->ignore($id),
                    ],
                'google_site_verification' => 'required',
                // 'meta_keywords' => 'required',
                'meta_keywords' => 'required|max:255',
                'canonical_url' => 'required',
                'short_description' => 'required|string|max:200',
                'seo_image' => 'required',
            ]);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Name is required.',
            'name_en.required' => 'English title is required.',
            'name_ar.required' => 'Arabic title is required.',
            'language.required' => 'Language is required.',
            'genres.required' => 'Genres are required.',
            'actors.required' => 'Actors are required.',
            'directors.required' => 'Directors are required.',
            'duration.required' => 'Duration is required.',
            'video_upload_type.required' => 'Video Type is required.',
            'release_date.required' => 'Release Date is required.',
            'description.required' => 'Description is required.',
            'description_en.required' => 'English description is required.',
            'description_ar.required' => 'Arabic description is required.',
            'IMDb_rating.required' => 'IMDb rating is required.',
            'IMDb_rating.numeric' => 'IMDb rating must be a number.',
            'IMDb_rating.min' => 'IMDb rating must be at least 1.',
            'IMDb_rating.max' => 'IMDb rating cannot be more than 10.',
            'description.string' => 'Description must be a string.',
            'discount.required' => 'Discount is required.',
            'discount.min' => 'Discount must be at least 1%.',
            'discount.max' => 'Discount cannot exceed 99%.',
            'access_duration.integer' => 'Access duration must be a valid number.',
            'access_duration.min' => 'Access duration must be greater than 0.',
            'available_for.integer' => 'Available for must be a valid number.',
            'available_for.min' => 'Available for must be greater than 0.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',

        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
