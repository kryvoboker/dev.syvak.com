<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Data\AppSettingsData;
use App\Http\Middleware\SetDefaultLocalePrefix;
use App\Supports\Services\AppSettingsService;
use App\Supports\Services\RequestLookupContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SetDefaultLocalePrefixTest extends TestCase
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

        DB::table('languages')->insert([
            ['id' => 1, 'code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'uk', 'name' => 'Ukrainian', 'is_active' => true, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $app_settings_service = new AppSettingsService();
        $app_settings_property = new \ReflectionProperty(AppSettingsService::class, 'app_settings_data');
        $app_settings_property->setValue($app_settings_service, AppSettingsData::fromArray([
            'language_id' => 1,
        ]));
        app()->instance(AppSettingsService::class, $app_settings_service);
    }

    public function test_it_uses_the_route_locale_when_switching_from_a_different_session_locale(): void
    {
        app(RequestLookupContext::class)->getLanguageByCode('en');

        $request = Request::create('/uk/page');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('locale', 'en');

        $route = Route::get('/{locale}/page', static fn (): string => 'ok')->name('localized.page');
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);

        $response = (new SetDefaultLocalePrefix())->handle(
            $request,
            static fn (): Response => response('ok'),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('uk', app()->getLocale());
        $this->assertSame(2, get_app_settings()?->language_id);
        $this->assertSame('uk', session('locale'));
    }
}
