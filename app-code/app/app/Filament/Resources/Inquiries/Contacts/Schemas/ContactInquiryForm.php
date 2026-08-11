<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\Schemas;

use App\Enums\Inquiries\InquiryStatusEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/inquiries/contacts.labels.message'))
                    ->schema([
                        Textarea::make('contact_message')
                            ->label(__('admin/inquiries/contacts.labels.message'))
                            ->rows(8),
                    ])
                    ->columnSpanFull(),
                Section::make(__('admin/inquiries/contacts.labels.model'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/inquiries/contacts.labels.name')),
                        TextInput::make('email')
                            ->label(__('admin/inquiries/contacts.labels.email')),
                        TextInput::make('phone')
                            ->label(__('admin/inquiries/contacts.labels.phone'))
                            ->formatStateUsing(fn (?string $state): string => is_string($state) ? parse_telephone($state) : ''),
                        Select::make('status')
                            ->label(__('admin/inquiries/contacts.labels.status'))
                            ->options(self::statusOptions())
                            ->required(),
                        TextInput::make('locale')
                            ->label(__('admin/inquiries/contacts.labels.locale')),
                        DateTimePicker::make('submitted_at')
                            ->label(__('admin/inquiries/contacts.labels.submitted_at')),
                        TextInput::make('source_url')
                            ->label(__('admin/inquiries/contacts.labels.source_url'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return collect(InquiryStatusEnum::cases())
            ->mapWithKeys(fn (InquiryStatusEnum $status): array => [
                $status->value => __('admin/inquiries/contacts.statuses.' . $status->value),
            ])
            ->all();
    }
}
