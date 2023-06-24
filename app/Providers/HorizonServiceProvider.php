<?php

namespace App\Providers;

use App\Apollo\Traits\UtilsTrait;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    use UtilsTrait;

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');

        Horizon::auth(function ($request) {
            $filter = false;
            //$clientIp = $request->getClientIp();
            $clientIp = $request->server->get('REMOTE_ADDR');
            foreach (config('apollo.horizon_ip_auth') as $val) {
                if (self::cidr_match($clientIp, $val)) {
                    $filter = true;
                }
            }

            return app()->environment('local') || $filter;
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
