<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Log::info(' ----- NEW REQUEST ----- ');
        Log::info(' $request->getMethod()                  = ' . $this->app->request->getMethod());
        Log::info(' $request->fullUrl()                    = ' . $this->app->request->fullUrl());
        Log::info(' $request->all()                        = ',  $this->app->request->all());
        Log::info(' $request->getSchemeAndHttpHost()       = ' . $this->app->request->getSchemeAndHttpHost());
        Log::info(' $request->ip()                         = ' . $this->app->request->ip());

        /**
         * The 'App\Http\Middleware/TrustProxies' is executed AFTER this 'AppServiceProvider' then the '$this->app->request->ip()'
         *  doesn't return the 'client' IP - from: header('x-forwarded-for') - but return the 'proxy' IP. To solve this problem I'll
         *  get the origina 'client' IP manually
         */
        if ($this->app->request->headers->has('x-forwarded-for')) {
            $clientIp = $this->app->request->headers->get('x-forwarded-for');
        } else {
            $clientIp = $this->app->request->ip();
        }
        Log::info(' $clientIp from "x-forwarded-for"   = ' . $clientIp . ' -> ' . gethostbyaddr($clientIp));
        Log::info(' $request->header()                 = ',  $this->app->request->header());
    }
}
