<?php

namespace App\Http\Requests;

use App\Models\Media;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.config('swag.media.max_kb'),
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value instanceof UploadedFile && Media::hasBlockedExtension($value->getClientOriginalName())) {
                        $fail("This file type isn't allowed. Put it in a zip file and upload that.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        $limit = (int) round(config('swag.media.max_kb') / 1024).' MB';

        return [
            'file.required' => "No file received. It may be larger than the server allows (limit {$limit}).",
            'file.uploaded' => "The file failed to upload. It may be larger than the server allows (limit {$limit}).",
            'file.max' => "The file is too large. The limit is {$limit}.",
        ];
    }
}
