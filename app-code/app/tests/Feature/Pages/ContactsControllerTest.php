<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Http\Controllers\Pages\ContactsController;
use App\Models\PageSettings\PageSetting;
use App\Models\Slug;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\PageSettings\ContactsPageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ContactsControllerTest extends TestCase
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

        $this->app->instance(HeaderService::class, new class () extends HeaderService {
            /** @return array<string, mixed> */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return ['categories' => []];
            }
        });
        $this->app->instance(FooterService::class, new class () extends FooterService {
            /** @return array<string, mixed> */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return [];
            }
        });
    }

    public function test_it_renders_contacts_page_by_localized_slug(): void
    {
        $page_setting = $this->createPageSetting();
        Slug::query()->create([
            'sluggable_id' => $page_setting->id,
            'sluggable_type' => PageSetting::class,
            'language_id' => 1,
            'slug' => 'contact-us',
        ]);

        $view = app(ContactsController::class)->show(
            app(HeaderService::class),
            app(FooterService::class),
            app(ContactsPageService::class),
            'en',
            'contact-us',
        );

        $this->assertInstanceOf(View::class, $view);
        $view_data = $view->getData();
        $this->assertSame('Contacts', $view_data['page_title']);
        $this->assertSame('/en/contact-us/contact', parse_url((string) $view_data['form_action'], PHP_URL_PATH));
    }

    public function test_it_renders_contacts_page_from_static_url_when_slug_is_not_configured(): void
    {
        $this->createPageSetting();
        app()->setLocale('uk');

        $view = app(ContactsController::class)->showStatic(
            app(HeaderService::class),
            app(FooterService::class),
            app(ContactsPageService::class),
            'uk',
        );

        $this->assertInstanceOf(View::class, $view);
        $view_data = $view->getData();
        $this->assertSame('Контакти', $view_data['page_title']);
        $this->assertSame('/uk/contacts/contact', parse_url((string) $view_data['form_action'], PHP_URL_PATH));
    }

    public function test_it_redirects_static_url_to_localized_slug_when_slug_is_configured(): void
    {
        $page_setting = $this->createPageSetting();
        Slug::query()->create([
            'sluggable_id' => $page_setting->id,
            'sluggable_type' => PageSetting::class,
            'language_id' => 1,
            'slug' => 'contact-us',
        ]);

        $response = app(ContactsController::class)->showStatic(
            app(HeaderService::class),
            app(FooterService::class),
            app(ContactsPageService::class),
            'en',
        );

        $this->assertSame('/en/contact-us', parse_url($response->getTargetUrl(), PHP_URL_PATH));
    }

    public function test_it_rejects_unknown_contacts_slug(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(ContactsController::class)->show(
            app(HeaderService::class),
            app(FooterService::class),
            app(ContactsPageService::class),
            'en',
            'missing-contacts-page',
        );
    }

    private function createPageSetting(): PageSetting
    {
        return PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_CONTACTS,
            'settings' => [
                'localized' => [
                    '1' => [
                        'title' => 'Contacts',
                        'working_hours' => ['title' => 'Working hours', 'content' => 'Mon-Fri'],
                    ],
                    '2' => [
                        'title' => 'Контакти',
                        'working_hours' => ['title' => 'Графік роботи', 'content' => 'Пн-Пт'],
                    ],
                ],
            ],
        ]);
    }
}
