<?php

declare(strict_types=1);

namespace App\Http\Requests\Pages;

use App\Services\PageSettings\ContactsPageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ContactsFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $language = resolve_language_by_locale(normalize_locale((string) $this->route('locale')));
        $contacts_page_service = app(ContactsPageService::class);
        $slug = (string) $this->route('slug', '');
        $page_setting = filled($slug)
            ? $contacts_page_service->findBySlug($slug, (int) $language?->id)
            : $contacts_page_service->getStaticPageSetting();

        if ($page_setting === null) {
            throw ValidationException::withMessages([
                'contact_form' => __('catalog/contacts.errors.page_not_found'),
            ]);
        }

        return $contacts_page_service->getFormRules($page_setting);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('catalog/contacts.fields.name'),
            'email' => __('catalog/contacts.fields.email'),
            'phone' => __('catalog/contacts.fields.phone'),
            'text' => __('catalog/contacts.fields.text'),
            'file' => __('catalog/contacts.fields.file'),
        ];
    }
}
