<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\Pages;

use App\Filament\Resources\Inquiries\Contacts\ContactInquiryResource;
use Filament\Resources\Pages\ListRecords;

class ListContactInquiries extends ListRecords
{
    protected static string $resource = ContactInquiryResource::class;

    public function getTitle(): string
    {
        return __('admin/inquiries/contacts.navigation_label');
    }

    public function getHeading(): ?string
    {
        return __('admin/inquiries/contacts.navigation_label');
    }
}
