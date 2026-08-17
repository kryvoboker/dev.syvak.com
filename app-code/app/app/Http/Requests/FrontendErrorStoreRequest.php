<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FrontendErrorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:window_error,unhandled_rejection,manual'],
            'message' => ['required', 'string', 'max:2000'],
            'stack' => ['nullable', 'string', 'max:10000'],
            'url' => ['required', 'url', 'max:2048'],
            'filename' => ['nullable', 'string', 'max:2048'],
            'line' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'column' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'user_agent' => ['nullable', 'string', 'max:1000'],
            'page_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
