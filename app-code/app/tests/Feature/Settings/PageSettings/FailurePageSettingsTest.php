<?php

declare(strict_types=1);

namespace Tests\Feature\Settings\PageSettings;

use App\Filament\Resources\PageSettings\Failure\FailureResource;
use App\Filament\Resources\PageSettings\Failure\Pages\EditFailure;
use App\Models\PageSettings\PageSetting;
use App\Services\PageSettings\FailurePageService;
use App\Services\PageSettings\PageSettingsBootstrapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FailurePageSettingsTest extends TestCase
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

    public function test_bootstrap_creates_a_normalized_failure_singleton(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapFailurePageSetting();

        $this->assertSame(PageSetting::PAGE_TYPE_FAILURE, $page_setting->page_type);
        $this->assertSame(1, (int) data_get($page_setting->settings, 'meta.contract_version'));
        $this->assertTrue((bool) data_get($page_setting->settings, 'buttons.retry.enabled'));
        $this->assertTrue((bool) data_get($page_setting->settings, 'support_contacts.use_contacts_emails'));
        $this->assertArrayHasKey('1', (array) data_get($page_setting->settings, 'localized'));
        $this->assertArrayHasKey('2', (array) data_get($page_setting->settings, 'localized'));
        $this->assertSame(1, PageSetting::query()->where('page_type', PageSetting::PAGE_TYPE_FAILURE)->count());
    }

    public function test_failure_settings_normalize_sorted_contacts_and_custom_classes(): void
    {
        $settings = app(PageSettingsBootstrapService::class)->normalizeFailureSettings([
            'support_contacts' => [
                'phones' => [
                    ['value' => ' second ', 'sort_order' => 20, 'custom_css_classes' => ' md:text-lg  '],
                    ['value' => 'first', 'sort_order' => 10, 'type' => 'landline'],
                ],
                'emails' => [
                    ['value' => ' test@example.com ', 'sort_order' => 1],
                ],
            ],
        ]);

        $this->assertSame('first', data_get($settings, 'support_contacts.phones.0.value'));
        $this->assertSame('landline', data_get($settings, 'support_contacts.phones.0.type'));
        $this->assertSame('md:text-lg', data_get($settings, 'support_contacts.phones.1.custom_css_classes'));
        $this->assertSame('test@example.com', data_get($settings, 'support_contacts.emails.0.value'));
    }

    public function test_failure_resource_is_a_singleton_edit_page(): void
    {
        $pages = FailureResource::getPages();

        $this->assertSame(['index'], array_keys($pages));
        $this->assertSame(EditFailure::class, $pages['index']->getPage());
        $this->assertFalse(FailureResource::canCreate());
        $this->assertFalse(FailureResource::canDeleteAny());
    }

    public function test_failure_page_service_resolves_localized_slugs_from_the_shared_table(): void
    {
        $page_setting = app(PageSettingsBootstrapService::class)->bootstrapFailurePageSetting();
        $page_setting->slugs()->create(['language_id' => 1, 'slug' => 'payment-failure']);

        $resolved = app(FailurePageService::class)->findBySlug('payment-failure', 1);

        $this->assertInstanceOf(PageSetting::class, $resolved);
        $this->assertSame($page_setting->id, $resolved->id);
        $this->assertSame('payment-failure', $resolved->getSlugByLanguageId(1));
    }
}
