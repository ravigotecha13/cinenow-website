<?php

namespace Modules\Ad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomAdsSettingRequest extends FormRequest
{
    /**
     * Custom ads are restricted to video creatives in the player placement (UI no longer exposes other options).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => 'video',
            'placement' => 'player',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['video'])],
            'url_type' => ['required', 'string'],
            'placement' => ['required', Rule::in(['player'])],
            // 'duration' => ['required'],
            // 'skip_enabled' => ['boolean'],
            // 'skip_after' => ['required_if:enable,1'],
            // 'target_content_type' => ['required'],
            // 'target_categories' => ['required'],
            // 'max_views' => ['required'],
            'status' => ['required'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.ad_name_required'),
            'type.required' => __('messages.ad_type_required'),
            'placement.required' => __('messages.placement_required'),
            // 'duration.required' => __('messages.duration_required'),
            // 'target_content_type.required' => __('messages.target_content_type_required'),
            // 'target_categories.required' => __('messages.target_categories_required'),
            // 'max_views.required' => __('messages.max_views_required'),
            'start_date.required' => __('messages.start_date_required'),
            'start_date.date' => __('messages.invalid_date'),
            'end_date.required' => __('messages.end_date_required'),
            'end_date.date' => __('messages.invalid_date'),
            'end_date.after_or_equal' => __('messages.end_date_greater'),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
