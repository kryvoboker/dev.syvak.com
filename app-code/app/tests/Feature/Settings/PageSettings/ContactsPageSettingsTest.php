<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Resources\PageSettings\Contacts\ContactsResource;
use App\Filament\Resources\PageSettings\Contacts\Pages\EditContacts;
use App\Models\PageSettings\PageSetting;
use App\Rules\ValidRegexMask;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactsPageSettingsTest extends TestCase
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
            $table->unique(['sluggable_type', 'sluggable_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_bootstrap_creates_one_normalized_contacts_page_setting(): void
    {
        $service = app(PageSettingsBootstrapService::class);

        $page_setting = $service->bootstrapContactsPageSetting();

        $this->assertSame(PageSetting::PAGE_TYPE_CONTACTS, $page_setting->page_type);
        $this->assertSame(1, (int) data_get($page_setting->settings, 'meta.contract_version'));
        $this->assertArrayHasKey('1', (array) data_get($page_setting->settings, 'localized'));
        $this->assertArrayHasKey('2', (array) data_get($page_setting->settings, 'localized'));
        $this->assertSame(['jpg', 'jpeg', 'png'], data_get($page_setting->settings, 'contact_form.fields.file.allowed_types'));
        $this->assertSame([], data_get($page_setting->settings, 'images'));

        $second_page_setting = $service->bootstrapContactsPageSetting();

        $this->assertSame($page_setting->id, $second_page_setting->id);
        $this->assertSame(1, PageSetting::query()->where('page_type', PageSetting::PAGE_TYPE_CONTACTS)->count());
    }

    public function test_contacts_settings_normalize_repeater_order_and_file_configuration(): void
    {
        $settings = app(PageSettingsBootstrapService::class)->normalizeContactsSettings([
            'images' => [
                [
                    'path' => 'images/contacts/two.png',
                    'sort_order' => 20,
                    'background' => '#ffffff',
                    'custom_css_classes' => '  md:w-1/2   opacity-75 ',
                ],
                [
                    'path' => 'images/contacts/one.png',
                    'sort_order' => 10,
                    'background' => 'invalid',
                ],
            ],
            'phones' => [
                ['type' => 'mobile', 'value' => '  +380 00 000 00 00  ', 'sort_order' => 20],
                ['type' => 'landline', 'value' => '  044 000 00 00  ', 'sort_order' => 10],
            ],
            'contact_form' => [
                'fields' => [
                    'file' => [
                        'allowed_types' => [' JPG ', 'jpeg', 'not valid!'],
                        'upload_path' => 'images/contacts/{year}/{month}',
                    ],
                ],
            ],
            'map' => [
                'latitude' => '50.4501',
                'longitude' => '30.5234',
            ],
        ]);

        $this->assertSame(10, data_get($settings, 'images.0.sort_order'));
        $this->assertSame('transparent', data_get($settings, 'images.0.background'));
        $this->assertSame('md:w-1/2 opacity-75', data_get($settings, 'images.1.custom_css_classes'));
        $this->assertSame('044 000 00 00', data_get($settings, 'phones.0.value'));
        $this->assertSame(['jpg', 'jpeg'], data_get($settings, 'contact_form.fields.file.allowed_types'));
        $this->assertSame('images/contacts/{year}/{month}', data_get($settings, 'contact_form.fields.file.upload_path'));
        $this->assertSame(50.4501, data_get($settings, 'map.latitude'));
        $this->assertSame(30.5234, data_get($settings, 'map.longitude'));
    }

    public function test_contacts_resource_is_a_singleton_edit_page(): void
    {
        $pages = ContactsResource::getPages();

        $this->assertSame(['index'], array_keys($pages));
        $this->assertSame(EditContacts::class, $pages['index']->getPage());
        $this->assertFalse(ContactsResource::canCreate());
        $this->assertFalse(ContactsResource::canDeleteAny());
    }

    public function test_contacts_regex_rule_accepts_valid_masks_and_rejects_invalid_masks(): void
    {
        $valid = Validator::make(['regex' => '/^[a-z]+$/'], [
            'regex' => [new ValidRegexMask()],
        ]);
        $invalid = Validator::make(['regex' => '/[/'], [
            'regex' => [new ValidRegexMask()],
        ]);

        $this->assertTrue($valid->passes());
        $this->assertTrue($invalid->fails());
    }
}
