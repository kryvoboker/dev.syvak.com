<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\RelationManagers;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin/inquiries/contacts.labels.responses'))
            ->columns([
                TextColumn::make('admin_name')
                    ->label(__('admin/inquiries/contacts.labels.admin_name'))
                    ->sortable(),
                TextColumn::make('subject')
                    ->label(__('admin/inquiries/contacts.labels.subject'))
                    ->searchable()
                    ->limit(80),
                TextColumn::make('recipient_email')
                    ->label(__('admin/inquiries/contacts.labels.recipient_email'))
                    ->placeholder('-'),
                TextColumn::make('delivery_status')
                    ->label(__('admin/inquiries/contacts.labels.delivery_status'))
                    ->formatStateUsing(fn (InquiryResponseDeliveryStatusEnum|string $state): string => __('admin/inquiries/contacts.statuses.' . ($state instanceof InquiryResponseDeliveryStatusEnum ? $state->value : $state)))
                    ->badge(),
                TextColumn::make('response_at')
                    ->label(__('admin/inquiries/contacts.labels.response_at'))
                    ->dateTime(config('app.datetime_format'), config('app.timezone'))
                    ->sortable(),
                TextColumn::make('sent_at')
                    ->label(__('admin/inquiries/contacts.labels.sent_at'))
                    ->dateTime(config('app.datetime_format'), config('app.timezone'))
                    ->placeholder('-'),
                TextColumn::make('body_html')
                    ->label(__('admin/inquiries/contacts.labels.body_html'))
                    ->html()
                    ->limit(120),
            ])
            ->paginated(false);
    }
}
