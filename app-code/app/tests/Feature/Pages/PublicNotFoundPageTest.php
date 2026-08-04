<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Models\ApplicationSettings\Language;
use App\Models\PageSettings\PageSetting;
use App\Services\FooterService;
use App\Services\HeaderService;
use App\Services\Modules\StorefrontModulePlacementResolverService;
use App\Services\PageSettings\NotFoundPageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicNotFoundPageTest extends TestCase
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

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('cart_mode')->default('regular');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->app->instance(HeaderService::class, new class () extends HeaderService {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return [
                    'logo_data' => [
                        'urls' => [
                            'original_thumb' => '',
                            'thumb_1x' => '',
                            'thumb_2x' => '',
                            'thumb_3x' => '',
                            'thumb_4x' => '',
                        ],
                        'width' => 1,
                        'height' => 1,
                    ],
                    'categories' => [],
                    'header_categories' => [],
                    'languages' => Language::query()->get(),
                    'sluggable_type' => null,
                    'slug' => null,
                    'variant_slug' => null,
                    'attribute_filters' => [],
                    'socials' => [],
                ];
            }
        });

        $this->app->instance(FooterService::class, new class () extends FooterService {
            /**
             * @return array<string, mixed>
             */
            public function __invoke(array $params = []): array
            {
                unset($params);

                return [
                    'subscription_data' => [
                        'title' => 'Subscribe',
                        'text' => 'Telegram',
                        'url' => '#',
                        'support_text' => 'Support',
                    ],
                    'contacts_data' => [
                        'title' => 'Contacts',
                        'phones' => [],
                        'find_us_label' => 'Find us',
                        'contacts_label' => 'Contacts',
                    ],
                    'information_data' => [
                        'title' => 'Information',
                        'items' => [],
                    ],
                    'menu_items' => [],
                    'social_items' => [],
                    'brand_large_text' => 'SYVAK',
                ];
            }
        });

        $this->app->instance(StorefrontModulePlacementResolverService::class, new class () extends StorefrontModulePlacementResolverService {
            public function __construct()
            {
            }

            /**
             * @return array<int, array<string, mixed>>
             */
            public function resolveForPlacement(string $placement, ?string $page_type = null): array
            {
                unset($placement, $page_type);

                return [];
            }
        });
    }

    public function testPublicStorefront404UsesLocalizedSettingsAndKeeps404Status(): void
    {
        PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
            'settings' => [
                'localized' => [
                    '1' => [
                        'title' => 'Page is missing',
                        'description' => 'The requested page does not exist.',
                        'link' => ['label' => 'Return home', 'url' => '/en'],
                    ],
                    '2' => [
                        'title' => 'Сторінку не знайдено',
                        'description' => 'Запитувана сторінка не існує.',
                        'link' => ['label' => 'На головну', 'url' => '/uk'],
                    ],
                ],
            ],
        ]);

        $this->withoutVite();

        $response = $this->get('/en/missing-page');

        $response
            ->assertStatus(404)
            ->assertSee('Page is missing')
            ->assertSee('The requested page does not exist.')
            ->assertSee('href="/en"', false);
    }

    public function testPublic404DoesNotReplaceJsonOrAdminResponses(): void
    {
        PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
            'settings' => ['localized' => ['1' => ['title' => 'Storefront 404']]],
        ]);

        $this->getJson('/en/missing-page')->assertStatus(404)->assertJsonStructure(['message']);
        $this->get('/en/alyo-admin/missing-page')->assertStatus(404)->assertDontSee('Storefront 404');
    }

    public function testNotFoundServiceOmitsMissingImagesAndUsesSafeHomeFallback(): void
    {
        PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
            'settings' => [
                'localized' => [
                    '1' => [
                        'title' => 'Missing page',
                        'link' => ['url' => 'javascript:alert(1)'],
                    ],
                ],
                'images' => [
                    'slot_1' => [
                        'path' => 'images/not-found/missing.png',
                        'sort_order' => 1,
                    ],
                ],
            ],
        ]);

        $data = app(NotFoundPageService::class)->getNotFoundData();

        $this->assertSame('Missing page', $data['title']);
        $this->assertSame([], $data['images']);
        $this->assertStringContainsString('/en', $data['link']['url']);
    }

    public function testNotFoundServiceReturnsExistingImagesInConfiguredOrder(): void
    {
        Storage::fake();
        $image_contents = (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
        Storage::put(
            'images/not-found/first.png',
            $image_contents,
        );
        Storage::put(
            'images/not-found/second.png',
            $image_contents,
        );

        PageSetting::query()->create([
            'page_type' => PageSetting::PAGE_TYPE_NOT_FOUND,
            'settings' => [
                'localized' => [
                    '1' => ['title' => 'Missing page'],
                ],
                'images' => [
                    'slot_1' => [
                        'path' => 'images/not-found/second.png',
                        'sort_order' => 20,
                    ],
                    'slot_2' => [
                        'path' => 'images/not-found/first.png',
                        'sort_order' => 10,
                    ],
                ],
            ],
        ]);

        $images = app(NotFoundPageService::class)->getNotFoundData()['images'];

        $this->assertCount(2, $images);
        $this->assertSame(10, $images[0]['sort_order']);
        $this->assertSame(20, $images[1]['sort_order']);
    }
}
