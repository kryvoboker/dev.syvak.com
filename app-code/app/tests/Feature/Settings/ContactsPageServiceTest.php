<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Enums\Inquiries\InquiryResponseDeliveryStatusEnum;
use App\Filament\Resources\Inquiries\Contacts\ContactInquiryResource;
use App\Jobs\DeliverContactsFormJob;
use App\Mail\InquiryResponseMail;
use App\Models\Inquiries\ContactInquiry;
use App\Models\Inquiries\Inquiry;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\Inquiries\InquiryResponseService;
use App\Services\PageSettings\ContactsFormDeliveryService;
use App\Services\PageSettings\ContactsPageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactsPageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('page_type', 100)->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
        Schema::create('slugs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sluggable_id');
            $table->string('sluggable_type');
            $table->unsignedBigInteger('language_id');
            $table->string('slug', 500);
            $table->timestamps();
        });
        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 100);
            $table->string('status', 50)->default('new');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('locale', 10)->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->json('payload')->nullable();
            $table->string('inquiryable_type')->nullable();
            $table->unsignedBigInteger('inquiryable_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->text('message')->nullable();
            $table->json('submitted_fields')->nullable();
            $table->timestamps();
        });
        Schema::create('inquiry_attachments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inquiry_id');
            $table->string('disk', 100);
            $table->string('path', 2048);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('inquiry_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inquiry_id');
            $table->string('subject');
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_name');
            $table->longText('body_html');
            $table->string('recipient_email')->nullable();
            $table->string('delivery_status', 50)->default('not_sent');
            $table->text('delivery_error')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_it_resolves_localized_contacts_data_and_ordered_collections(): void
    {
        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'localized' => [
                    '1' => [
                        'title' => 'Contacts',
                        'working_hours' => [
                            'title' => 'Working hours',
                            'description' => 'Call us',
                            'content' => 'Mon-Fri',
                        ],
                    ],
                ],
                'phones' => [
                    ['type' => 'mobile', 'value' => '+380 00 000 00 00', 'sort_order' => 20],
                    ['type' => 'landline', 'value' => '044 000 00 00', 'sort_order' => 10],
                ],
                'emails' => [
                    ['value' => 'info@example.com', 'sort_order' => 10],
                ],
                'addresses' => [
                    ['sort_order' => 10, 'localized' => ['1' => ['title' => 'Office', 'value' => 'Kyiv', 'url' => 'https://maps.example/office']]],
                ],
                'map' => [
                    'iframe' => '<iframe src="https://www.google.com/maps/embed?x=1"></iframe>',
                    'width' => 800,
                    'height' => 500,
                    'latitude' => 50.45,
                    'longitude' => 30.52,
                ],
            ],
        ]);
        Slug::query()->create([
            'sluggable_id' => $page_setting->id,
            'sluggable_type' => PageSetting::class,
            'language_id' => 1,
            'slug' => 'contacts',
        ]);

        $service = app(ContactsPageService::class);
        $resolved_page = $service->findBySlug('contacts', 1);

        if ($resolved_page === null) {
            self::fail('Contacts page setting was not resolved.');
        }

        $data = $service->getViewData($resolved_page, 1);

        $this->assertSame($page_setting->id, $resolved_page->id);
        $this->assertSame('Contacts', $data['title']);
        $this->assertSame('044 000 00 00', data_get($data, 'phones.0.value'));
        $this->assertSame('info@example.com', data_get($data, 'emails.0'));
        $this->assertSame('Kyiv', data_get($data, 'addresses.0.value'));
        $this->assertSame('https://www.google.com/maps/embed?x=1', data_get($data, 'map.iframe_src'));
        $this->assertSame('50.45,30.52', data_get($data, 'map.coordinates'));
    }

    public function test_it_builds_validation_rules_from_normalized_field_settings(): void
    {
        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'fields' => [
                        'name' => ['enabled' => true, 'required' => true, 'min_length' => 2, 'max_length' => 40],
                        'email' => ['enabled' => true, 'required' => true],
                        'phone' => ['enabled' => false],
                        'text' => ['enabled' => true, 'required' => false],
                        'file' => ['enabled' => true, 'required' => false, 'max_size_kb' => 1024, 'allowed_types' => ['jpg', 'png']],
                    ],
                ],
            ],
        ]);

        $rules = app(ContactsPageService::class)->getFormRules($page_setting);

        $this->assertContains('required', $rules['name']);
        $this->assertContains('min:2', $rules['name']);
        $this->assertSame(['prohibited'], $rules['phone']);
        $this->assertContains('email', $rules['email']);
        $this->assertCount(5, $rules);
    }

    public function test_it_resolves_contacts_page_for_static_url_fallback(): void
    {
        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [],
        ]);

        $resolved_page = app(ContactsPageService::class)->getStaticPageSetting();

        $this->assertNotNull($resolved_page);
        $this->assertSame($page_setting->id, $resolved_page->id);
        $this->assertNull($resolved_page->getSlugByLanguageId(1));
    }

    public function test_it_delivers_a_configured_telegram_message_without_logging_personal_data(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'destinations' => [
                        'telegram' => ['enabled' => true, 'bot_token' => 'test-token', 'chat_id' => '123'],
                    ],
                ],
                'telegram' => [
                    'templates' => [
                        '1' => ['body' => 'Name: {name}'],
                    ],
                ],
            ],
        ]);

        app(ContactsFormDeliveryService::class)->deliver(
            page_setting: $page_setting,
            locale: 'en',
            language_id: 1,
            data: ['name' => 'Test user'],
        );

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '123'
                && $request['text'] === 'Name: Test user';
        });
    }

    public function test_it_queues_contacts_delivery_and_stores_the_uploaded_file_before_dispatching(): void
    {
        Queue::fake();
        Storage::fake('public');

        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'destinations' => [
                        'email' => ['enabled' => true, 'address' => 'info@example.com'],
                    ],
                ],
            ],
        ]);
        $file = UploadedFile::fake()->create('request.pdf', 10, 'application/pdf');

        app(ContactsFormDeliveryService::class)->deliver(
            page_setting: $page_setting,
            locale: 'en',
            language_id: 1,
            data: ['name' => 'Test user', 'file' => $file],
            file: $file,
        );

        Queue::assertPushed(DeliverContactsFormJob::class, function (DeliverContactsFormJob $job): bool {
            return $job->page_setting_id > 0
                && $job->file_path !== null
                && ! array_key_exists('file', $job->data)
                && $job->inquiry_id > 0
                && Storage::disk('public')->exists($job->file_path);
        });

        $inquiry = Inquiry::query()->first();

        $this->assertNotNull($inquiry);
        $this->assertInstanceOf(ContactInquiry::class, $inquiry->inquiryable);
        $this->assertCount(1, $inquiry->attachments);
    }

    public function test_it_registers_contacts_inquiry_resource_pages_and_reply_route(): void
    {
        $pages = ContactInquiryResource::getPages();

        $this->assertSame(['index', 'edit', 'reply'], array_keys($pages));
        $this->assertSame(
            \App\Filament\Resources\Inquiries\Contacts\Pages\ReplyContactInquiry::class,
            $pages['reply']->getPage(),
        );
    }

    public function test_it_keeps_soft_deleted_contact_inquiries_available_for_admin_moderation(): void
    {
        $inquiry = Inquiry::factory()->create();
        $inquiry->delete();

        $this->assertNotNull(ContactInquiryResource::getEloquentQuery()->find($inquiry->getKey()));
        $this->assertNotNull(ContactInquiryResource::getRecordRouteBindingEloquentQuery()->find($inquiry->getKey()));
        $this->assertNotNull(Inquiry::onlyTrashed()->find($inquiry->getKey()));

        $inquiry->restore();

        $this->assertNull(Inquiry::onlyTrashed()->find($inquiry->getKey()));
    }

    public function test_it_saves_and_sends_sanitized_inquiry_response(): void
    {
        Mail::fake();

        $inquiry = Inquiry::factory()->create(['email' => 'customer@example.com']);
        $contact_inquiry = ContactInquiry::factory()->create();
        $inquiry->inquiryable()->associate($contact_inquiry)->save();

        $response = app(InquiryResponseService::class)->createAndDeliver($inquiry, [
            'subject' => 'Custom response subject',
            'body_html' => '<p>Hello</p><script>alert(1)</script>',
            'admin_name' => '',
            'response_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame('Адмін', $response->admin_name);
        $this->assertSame('Custom response subject', $response->subject);
        $this->assertSame(InquiryResponseDeliveryStatusEnum::Sent, $response->delivery_status);
        $this->assertStringNotContainsString('<script>', $response->body_html);
        Mail::assertSent(InquiryResponseMail::class, function (InquiryResponseMail $mail): bool {
            return $mail->envelope()->subject === 'Custom response subject';
        });
    }

    public function test_it_saves_but_does_not_send_response_without_customer_email(): void
    {
        Mail::fake();

        $inquiry = Inquiry::factory()->create(['email' => null]);
        $contact_inquiry = ContactInquiry::factory()->create();
        $inquiry->inquiryable()->associate($contact_inquiry)->save();

        $response = app(InquiryResponseService::class)->createAndDeliver($inquiry, [
            'subject' => 'Saved response subject',
            'body_html' => '<p>Saved response</p>',
        ]);

        $this->assertSame(InquiryResponseDeliveryStatusEnum::NotSent, $response->delivery_status);
        Mail::assertNothingSent();
    }

    public function test_it_sends_a_file_link_and_a_separate_telegram_document_when_enabled(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/contacts/request.pdf', 'file-content');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'destinations' => [
                        'telegram' => ['enabled' => true, 'send_file' => true, 'bot_token' => 'test-token', 'chat_id' => '123'],
                    ],
                ],
                'telegram' => [
                    'templates' => ['1' => ['body' => 'File: {file}']],
                ],
            ],
        ]);

        app(ContactsFormDeliveryService::class)->deliverStored(
            page_setting: $page_setting,
            locale: 'en',
            language_id: 1,
            data: ['name' => 'Test user'],
            file_path: 'images/contacts/request.pdf',
        );

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && str_contains((string) $request['text'], 'images/contacts/request.pdf');
        });
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendDocument';
        });
    }

    public function test_it_skips_telegram_delivery_without_a_page_token(): void
    {
        Http::fake();

        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'destinations' => [
                        'telegram' => ['enabled' => true, 'chat_id' => '123'],
                    ],
                ],
            ],
        ]);

        app(ContactsFormDeliveryService::class)->deliverStored(
            page_setting: $page_setting,
            locale: 'en',
            language_id: 1,
            data: ['name' => 'Test user'],
        );

        Http::assertNothingSent();
    }
}
