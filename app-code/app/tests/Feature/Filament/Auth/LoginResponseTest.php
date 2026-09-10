<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Auth;

use App\Filament\Auth\Http\Responses\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoginResponseTest extends TestCase
{
    public function test_it_redirects_to_the_dashboard_using_the_selected_admin_locale(): void
    {
        config()->set('app.allowed_locales', ['en', 'uk']);
        session()->put('locale', 'uk');
        Filament::setCurrentPanel(Filament::getPanel('alyo-admin'));

        $response = app(LoginResponse::class)->toResponse(Request::create('/uk/alyo-admin/login'));

        $this->assertSame('/uk/alyo-admin', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }
}
