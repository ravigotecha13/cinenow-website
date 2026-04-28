@extends('backend.layouts.app')

@section('content')
<x-back-button-component route="backend.genres.index" />
{{ html()->form('POST', route('backend.genres.store'))
    ->attribute('enctype', 'multipart/form-data')
    ->attribute('data-toggle', 'validator')
    ->attribute('id', 'form-submit')
    ->class('requires-validation')
    ->attribute('novalidate', 'novalidate')
    ->open()
}}
<div class="card">
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-12 col-xl-3 position-relative">
                {{ html()->label(__('messages.image'), 'Image')->class('form-label')}}
                <div class="input-group btn-file-upload">
                    {{ html()->button(__('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image')))
                        ->class('input-group-text form-control')
                        ->type('button')
                        ->attribute('data-bs-toggle', 'modal')
                        ->attribute('data-bs-target', '#exampleModal')
                        ->attribute('data-image-container', 'selectedImageContainer1')
                        ->attribute('data-hidden-input', 'file_url1')
                    }}

                    {{ html()->text('image_input1')
                        ->class('form-control')
                        ->placeholder(__('placeholder.lbl_image'))
                        ->attribute('aria-label', 'Image Input 1')
                        ->attribute('data-bs-toggle', 'modal')
                        ->attribute('data-bs-target', '#exampleModal')
                        ->attribute('data-image-container', 'selectedImageContainer1')
                        ->attribute('data-hidden-input', 'file_url1')
                        ->attribute('aria-describedby', 'basic-addon1')
                    }}
                </div>

                <div class="mb-3 uploaded-image" id="selectedImageContainer1">
                    @if (old('file_url'))
                        <img src="{{ old('file_url') }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                    @endif
                </div>
                {{ html()->hidden('file_url')->id('file_url1')->value(old('file_url', '')) }}

            </div>
            <div class="col-xl-9">
                <div class="row gy-3">
                    <div class="col-md-6 col-lg-6">
                        <div class="mb-3">
                            {{ html()->label(__('genres.lbl_name') . ' (EN)<span class="text-danger">*</span>', 'name_en')->class('form-label')}}
                            {{
                                html()->text('name_en', old('name_en'))
                                    ->class('form-control')
                                    ->id('name_en')
                                    ->placeholder(__('placeholder.lbl_genre_name'))
                                    ->attribute('required', 'required')
                            }}
                            @error('name_en')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-en-error">English name is required</div>
                        </div>
                        <div class="mb-3">
                            {{ html()->label(__('genres.lbl_name') . ' (AR)', 'name_ar')->class('form-label')}}
                            {{
                                html()->text('name_ar', old('name_ar'))
                                    ->class('form-control')
                                    ->id('name_ar')
                                    ->placeholder(__('placeholder.lbl_genre_name'))
                                    ->attribute('dir', 'rtl')
                            }}
                            @error('name_ar')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                            <div class="d-flex justify-content-between align-items-center form-control">
                                {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0') }}
                                <div class="form-check form-switch">
                                    {{ html()->hidden('status', 0) }}
                                    {{
                                        html()->checkbox('status', old('status', 1))
                                            ->class('form-check-input')
                                            ->id('status')
                                            ->value(1)
                                    }}
                                </div>
                            </div>
                            @error('status')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6">
                        {{ html()->label(__('plan.lbl_description') . ' (EN) <span class="text-danger">*</span>', 'description_en')->class('form-label') }}
                        {{
                            html()->textarea('description_en', old('description_en'))
                                ->class('form-control')
                                ->id('description_en')
                                ->placeholder(__('placeholder.lbl_genre_description'))
                                ->rows('5')
                                ->attribute('required', 'required')
                        }}
                        @error('description_en')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="description-en-error">English description is required</div>
                        <div class="mt-3">
                            {{ html()->label(__('plan.lbl_description') . ' (AR)', 'description_ar')->class('form-label') }}
                            {{
                                html()->textarea('description_ar', old('description_ar'))
                                    ->class('form-control')
                                    ->id('description_ar')
                                    ->placeholder(__('placeholder.lbl_genre_description'))
                                    ->rows('5')
                                    ->attribute('dir', 'rtl')
                            }}
                            @error('description_ar')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<div class="d-grid d-sm-flex justify-content-sm-end gap-3">
    {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
</div>

{{ html()->form()->close() }}

@include('components.media-modal')

@endsection
