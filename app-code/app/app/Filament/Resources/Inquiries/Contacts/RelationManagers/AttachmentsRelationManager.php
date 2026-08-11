<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\RelationManagers;

use App\Models\Inquiries\InquiryAttachment;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin/inquiries/contacts.labels.attachments'))
            ->columns([
                TextColumn::make('original_name')
                    ->label(__('admin/inquiries/contacts.labels.original_name')),
                TextColumn::make('mime_type')
                    ->label(__('admin/inquiries/contacts.labels.mime_type')),
                TextColumn::make('size')
                    ->label(__('admin/inquiries/contacts.labels.size'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state / 1024, 2) . ' KB'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__('admin/inquiries/contacts.actions.download'))
                    ->icon(Heroicon::ArrowDownTray)
                    ->url(fn (InquiryAttachment $record): string => route('admin.inquiries.attachments.download', ['attachment' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
