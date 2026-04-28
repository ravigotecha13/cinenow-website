<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\ChatGTPService;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        ini_set('memory_limit', '512M');
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);

        $this->app->singleton(ChatGTPService::class, function ($app) {
            return new ChatGTPService();
        });

        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->configureStripeSslCaBundle();

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ((dbConnectionStatus() && Schema::hasTable('users') && file_exists(storage_path('installed')) )) {
                if (in_array($event->command, ['migrate:fresh', 'db:wipe', 'db:seed'])) {
                    throw new \Exception("❌ Command '{$event->command}' is blocked in production.");
                }
            }
        });

        Paginator::useBootstrap();

        Blade::directive('hasPermission', function ($permissions) {
            return "<?php if(auth()->user()?->can({$permissions}) ?? false): ?>";
        });

        Blade::directive('endhasPermission', function () {
            return '<?php endif; ?>';
        });

        $this->app->singleton('translation.loader', function ($app) {
            return new CustomTranslationLoader($app['files'], $app['path.lang']);
        });

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        if (dbConnectionStatus() && Schema::hasTable('settings') && file_exists(storage_path('installed'))) {
            $settings = Setting::getAllSettings()
                ->whereIn('name', ['google_client_id', 'google_client_secret', 'google_redirect_uri'])
                ->pluck('val', 'name');

            $googleClientId = $settings->get('google_client_id');
            $googleClientSecret = $settings->get('google_client_secret');
            $googleRedirectUri = $settings->get('google_redirect_uri');

            if (!empty($googleClientId) && !empty($googleClientSecret)) {
                config([
                    'services.google.client_id' => $googleClientId,
                    'services.google.client_secret' => $googleClientSecret,
                    'services.google.redirect' => $googleRedirectUri ?: config('services.google.redirect'),
                ]);
            }
        }
    }

    /**
     * Stripe-php sets CURLOPT_CAINFO to its bundled Mozilla CA file. On some Windows/XAMPP PHP+cURL
     * builds that triggers "unable to get local issuer certificate" (errno 60) even when php.ini
     * curl.cainfo works. Prefer the same CA bundle PHP already uses, or STRIPE_CAINFO in .env.
     */
    protected function configureStripeSslCaBundle(): void
    {
        if (! class_exists(\Stripe\Stripe::class)) {
            return;
        }

        $path = config('services.stripe.cainfo');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            $path = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        }

        if (is_string($path) && $path !== '' && is_readable($path)) {
            \Stripe\Stripe::$caBundlePath = $path;
        }
    }
}
