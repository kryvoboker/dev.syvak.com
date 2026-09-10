<?php

declare(strict_types=1);

namespace App\Filament\Resources\Inquiries\Contacts\Pages;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Enums\Inquiries\InquiryTypeEnum;
use App\Filament\Resources\Inquiries\Contacts\ContactInquiryResource;
use App\Models\Inquiries\Inquiry;
use App\Services\Inquiries\InquiryResponseService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use LogicException;

class ReplyContactInquiry extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = ContactInquiryResource::class;

    protected string $view = 'filament.pages.inquiries.reply-contact-inquiry';

    public int|string|null $record = null;

    public ?Inquiry $inquiry = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    #[Url]
    public ?string $activeTab = null;

    public function mount(int|string $record): void
    {
        $this->record = $record;
        $this->inquiry = Inquiry::withTrashed()
            ->ofType(InquiryTypeEnum::Contacts)
            ->with(['inquiryable', 'responses'])
            ->findOrFail($record);

        abort_unless(ContactInquiryResource::canView($this->inquiry), 403);

        $this->getReplyForm()->fill($this->getInitialFormData());
        $this->mountInteractsWithTable();
    }

    public function hydrate(): void
    {
        abort_unless($this->inquiry instanceof Inquiry && ContactInquiryResource::canView($this->inquiry), 403);
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin/inquiries/contacts.reply.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('admin/inquiries/contacts.reply.title');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('admin/default.buttons.back'))
                ->icon(Heroicon::ArrowLeft)
                ->url(fn (): string => ContactInquiryResource::getUrl('edit', ['record' => $this->getInquiryRecord()])),
            Action::make('send')
                ->label(__('admin/inquiries/contacts.reply.send'))
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->action(function (): void {
                    $this->sendResponse();
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('inquiry-reply-form')
                    ->livewireSubmitHandler('sendResponse')
                    ->footer([
                        Actions::make([
                            Action::make('sendResponse')
                                ->label(__('admin/inquiries/contacts.reply.send'))
                                ->icon(Heroicon::PaperAirplane)
                                ->color('success')
                                ->action(function (): void {
                                    $this->sendResponse();
                                }),
                        ]),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('admin/inquiries/contacts.labels.model'))
                    ->schema([
                        TextInput::make('contact_name')
                            ->label(__('admin/inquiries/contacts.labels.name'))
                            ->default($this->getInquiryRecord()->name),
                        TextInput::make('recipient_email')
                            ->label(__('admin/inquiries/contacts.labels.email'))
                            ->default(fn (): string => $this->getInquiryRecord()->email),
                    ])
                    ->columns(),
                Section::make(__('admin/inquiries/contacts.reply.title'))
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('admin/inquiries/contacts.reply.subject'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('body_html')
                            ->label(__('admin/inquiries/contacts.reply.body'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('admin_name')
                            ->label(__('admin/inquiries/contacts.reply.admin_name'))
                            ->helperText(__('admin/inquiries/contacts.reply.admin_name_helper'))
                            ->nullable(),
                        DateTimePicker::make('response_at')
                            ->label(__('admin/inquiries/contacts.reply.response_at'))
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getInquiryRecord()->responses()->getQuery())
            ->columns([
                TextColumn::make('admin_name')
                    ->label(__('admin/inquiries/contacts.labels.admin_name')),
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
                    ->dateTime(config('app.datetime_format'), config('app.timezone')),
                TextColumn::make('body_html')
                    ->label(__('admin/inquiries/contacts.labels.body_html'))
                    ->html()
                    ->limit(120),
            ])
            ->paginated(false);
    }

    private function sendResponse(): void
    {
        $data = $this->getReplyForm()->getState();
        $response = app(InquiryResponseService::class)->createAndDeliver($this->getInquiryRecord(), $data);
        $status = $response->delivery_status;

        if ($status === InquiryResponseDeliveryStatusEnum::Sent) {
            Notification::make('inquiry-response-sent')
                ->title(__('admin/default.success.title'))
                ->body(__('admin/inquiries/contacts.reply.saved', ['email' => $response->recipient_email]))
                ->success()
                ->persistent()
                ->send();
        } elseif ($status === InquiryResponseDeliveryStatusEnum::Failed) {
            Notification::make('inquiry-response-failed')
                ->title(__('admin/default.errors.title'))
                ->body(__('admin/inquiries/contacts.reply.failed', ['email' => $response->recipient_email]))
                ->danger()
                ->persistent()
                ->send();
        } else {
            Notification::make('inquiry-response-not-sent')
                ->title(__('admin/default.success.title'))
                ->body(__('admin/inquiries/contacts.reply.not_sent'))
                ->warning()
                ->persistent()
                ->send();
        }

        $this->resetTable();
        $this->getReplyForm()->fill($this->getInitialFormData());
    }

    private function getReplyForm(): Schema
    {
        $form = $this->getSchema('form');

        if (! $form instanceof Schema) {
            throw new LogicException('Inquiry reply form schema is not initialized.');
        }

        return $form;
    }

    private function getInquiryRecord(): Inquiry
    {
        if (! $this->inquiry instanceof Inquiry) {
            throw new LogicException('Contact inquiry record is not initialized.');
        }

        return $this->inquiry;
    }

    /** @return array<string, mixed> */
    private function getInitialFormData(): array
    {
        $inquiry = $this->getInquiryRecord();

        return [
            'contact_name' => $inquiry->name,
            'recipient_email' => $inquiry->email,
            'subject' => null,
            'body_html' => null,
            'admin_name' => null,
            'response_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
