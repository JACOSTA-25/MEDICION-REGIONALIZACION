<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure rate limits for public-facing endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-survey', function (Request $request): array {
            $minuteLimit = (int) config('security.rate_limits.public_survey.per_minute', 30);
            $hourLimit = (int) config('security.rate_limits.public_survey.per_hour', 300);
            $response = $this->buildFormRateLimitResponse(
                'Se alcanzo el limite temporal de envios. Espera un momento antes de volver a intentarlo.'
            );

            return [
                Limit::perMinute($minuteLimit)
                    ->by($this->rateLimiterKey('public-survey-minute', $request))
                    ->response($response),
                Limit::perHour($hourLimit)
                    ->by($this->rateLimiterKey('public-survey-hour', $request))
                    ->response($response),
            ];
        });

        RateLimiter::for('survey-catalogs', function (Request $request): array {
            $minuteLimit = (int) config('security.rate_limits.survey_catalogs.per_minute', 120);
            $hourLimit = (int) config('security.rate_limits.survey_catalogs.per_hour', 1200);
            $response = $this->buildJsonRateLimitResponse(
                'Se alcanzo el limite temporal de consultas. Espera un momento antes de actualizar el formulario.'
            );

            return [
                Limit::perMinute($minuteLimit)
                    ->by($this->rateLimiterKey('survey-catalogs-minute', $request))
                    ->response($response),
                Limit::perHour($hourLimit)
                    ->by($this->rateLimiterKey('survey-catalogs-hour', $request))
                    ->response($response),
            ];
        });
    }

    private function buildFormRateLimitResponse(string $message): callable
    {
        return function (Request $request, array $headers) use ($message) {
            $destination = url()->previous();

            if (! is_string($destination) || trim($destination) === '') {
                $destination = route('survey.create');
            }

            $response = redirect()
                ->to($destination)
                ->withInput($request->except('contact_name'))
                ->withErrors(['rate_limit' => $message]);

            $response->headers->add($headers);

            return $response;
        };
    }

    private function buildJsonRateLimitResponse(string $message): callable
    {
        return static fn (Request $request, array $headers) => response()->json([
            'message' => $message,
        ], 429, $headers);
    }

    private function rateLimiterKey(string $prefix, Request $request): string
    {
        return implode('|', [
            $prefix,
            (string) $request->ip(),
            sha1((string) ($request->userAgent() ?? 'unknown-agent')),
        ]);
    }
}
