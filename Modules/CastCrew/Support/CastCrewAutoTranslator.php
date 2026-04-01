<?php

namespace Modules\CastCrew\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stichoza\GoogleTranslate\GoogleTranslate;

/**
 * Translates cast/crew strings for API responses when locale is Arabic (no DB columns).
 * Uses the same unofficial Google Translate client as other PHP tooling in this project.
 */
final class CastCrewAutoTranslator
{
    public static function enabled(): bool
    {
        if (app()->getLocale() !== 'ar') {
            return false;
        }

        return filter_var(env('CAST_AUTO_TRANSLATE_AR', true), FILTER_VALIDATE_BOOLEAN);
    }

    public static function translate(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        if (! self::enabled()) {
            return $text;
        }

        $cacheKey = 'cast_auto_tr:v1:ar:' . md5($text);

        try {
            return Cache::remember($cacheKey, (int) env('CAST_AUTO_TRANSLATE_TTL', 604800), function () use ($text) {
                $tr = new GoogleTranslate('ar');
                $tr->setSource(null);

                return $tr->translate($text);
            });
        } catch (\Throwable $e) {
            Log::debug('CastCrewAutoTranslator: '.$e->getMessage());

            return $text;
        }
    }
}
