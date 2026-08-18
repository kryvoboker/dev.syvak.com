<?php

declare(strict_types=1);

namespace App\Http\Requests\Ajax;

use Illuminate\Support\Arr;

class LoadMoreProductsByAjaxIndexRequest extends CatalogFilterAjaxIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    #[\Override]
    public function rules(): array
    {
        $page_types = collect([
            config('page-settings.page_type.category'),
            config('page-settings.page_type.search'),
        ])->map(fn (mixed $page_type): string => is_scalar($page_type) ? (string) $page_type : '')->implode(',');

        return array_merge(parent::rules(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'page_type' => ['required', 'string', 'in:' . $page_types],
        ]);
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $normalized_data = $this->all();
        $per_page = $this->query('per_page');

        if ($per_page !== null && $per_page !== '') {
            Arr::set($normalized_data, 'per_page', $per_page);
        }

        $this->replace($normalized_data);
    }
}
