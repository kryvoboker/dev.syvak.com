<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;

class LiveSearchProductsAjaxIndexRequest extends FormRequest
{
    /**
     * @return array<string, array>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
