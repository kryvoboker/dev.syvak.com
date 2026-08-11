<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts;

use App\Enums\Inquiries\InquiryTypeEnum;
use App\Filament\Navigation\AdminNavigationGroupEnum;
use App\Filament\Resources\Inquiries\Contacts\Pages\EditContactInquiry;
use App\Filament\Resources\Inquiries\Contacts\Pages\ListContactInquiries;
use App\Filament\Resources\Inquiries\Contacts\Pages\ReplyContactInquiry;
use App\Filament\Resources\Inquiries\Contacts\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Inquiries\Contacts\RelationManagers\ResponsesRelationManager;
use App\Filament\Resources\Inquiries\Contacts\Schemas\ContactInquiryForm;
use App\Filament\Resources\Inquiries\Contacts\Tables\ContactInquiriesTable;
use App\Models\Inquiries\Inquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContactInquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Envelope;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Inquiries;

    public static function form(Schema $schema): Schema
    {
        return ContactInquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactInquiriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('type', InquiryTypeEnum::Contacts->value)
            ->with(['inquiryable', 'attachments', 'responses']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('type', InquiryTypeEnum::Contacts->value);
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactInquiries::route('/'),
            'edit' => EditContactInquiry::route('/{record}/edit'),
            'reply' => ReplyContactInquiry::route('/{record}/reply'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/inquiries/contacts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/inquiries/contacts.labels.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/inquiries/contacts.labels.plural_model');
    }
}
