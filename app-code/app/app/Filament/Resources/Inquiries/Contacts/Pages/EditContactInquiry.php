<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\Pages;

use App\Filament\Resources\Inquiries\Contacts\ContactInquiryResource;
use App\Models\Inquiries\Inquiry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use LogicException;

class EditContactInquiry extends EditRecord
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

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getInquiryRecord();
        $record->loadMissing('inquiryable');
        $data['contact_message'] = data_get($record->inquiryable, 'message');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin/default.buttons.save'))
                ->icon(Heroicon::CheckCircle)
                ->action(function (): void {
                    $this->save();
                }),
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->requiresConfirmation()
                ->after(function (Inquiry $record): void {
                    self::logMutation('soft_delete', $record);
                }),
            RestoreAction::make()
                ->label(__('admin/inquiries/contacts.actions.restore'))
                ->icon(Heroicon::ArrowPath)
                ->requiresConfirmation()
                ->after(function (Inquiry $record): void {
                    self::logMutation('restore', $record);
                }),
            ForceDeleteAction::make()
                ->label(__('admin/inquiries/contacts.actions.force_delete'))
                ->icon(Heroicon::Trash)
                ->requiresConfirmation()
                ->after(function (Inquiry $record): void {
                    self::logMutation('force_delete', $record);
                }),
            Action::make('reply')
                ->label(__('admin/inquiries/contacts.actions.reply'))
                ->icon(Heroicon::ChatBubbleLeftRight)
                ->url(fn (): string => ContactInquiryResource::getUrl('reply', ['record' => $this->getRecord()])),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    private function getInquiryRecord(): Inquiry
    {
        $record = $this->getRecord();

        if (! $record instanceof Inquiry) {
            throw new LogicException('Contact inquiry record has invalid type.');
        }

        return $record;
    }

    private static function logMutation(string $action, Inquiry $record): void
    {
        Log::channel('daily')->info('[EditContactInquiry] inquiry mutation completed', [
            'action' => $action,
            'admin_user_id' => auth()->id(),
            'inquiry_id' => $record->getKey(),
        ]);
    }
}
