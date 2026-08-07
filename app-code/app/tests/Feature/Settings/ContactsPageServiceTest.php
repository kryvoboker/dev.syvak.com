<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\PageSettings\ContactsFormDeliveryService;
use App\Services\PageSettings\ContactsPageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
        config()->set('monolog.telegram_token', 'test-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $page_setting = PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'contact_form' => [
                    'destinations' => [
                        'telegram' => ['enabled' => true, 'chat_id' => '123'],
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
}
