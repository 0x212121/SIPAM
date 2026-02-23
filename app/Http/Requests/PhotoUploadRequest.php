<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhotoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:50'],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:51200', // 50MB max per file
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.required' => 'Please select at least one photo to upload.',
            'photos.array' => 'Invalid photo upload format.',
            'photos.min' => 'Please select at least one photo to upload.',
            'photos.max' => 'You can only upload up to 50 photos at once.',
            'photos.*.image' => 'The file must be an image.',
            'photos.*.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif, webp.',
            'photos.*.max' => 'Each image may not be larger than 50MB.',
        ];
    }
}
