<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $is_local = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($is_local) {
            return $is_local ||
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    public function boot(): void
    {
        parent::boot();

        Telescope::auth(function (Request $request) {
            $login = config('telescope.auth_credentials.login');
            $pass = config('telescope.auth_credentials.password');
            $user = $request->user();

            if (
                ! ($user instanceof User) ||
                $request->getUser() !== $login ||
                $request->getPassword() !== $pass
            ) {
                header('WWW-Authenticate: Basic realm="Telescope"');
                header('HTTP/1.0 401 Unauthorized');

                echo 'Authentication required.';

                exit();
            }

            return true;
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            $allowed_emails = config('telescope.allowed_emails', []);

            return is_array($allowed_emails) && in_array($user->email, $allowed_emails, true);
        });
    }
}
