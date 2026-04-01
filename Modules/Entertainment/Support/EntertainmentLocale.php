<?php

namespace Modules\Entertainment\Support;

/**
 * Localized display fields for API responses (uses app locale from global-localization / middleware).
 */
final class EntertainmentLocale
{
    public static function isArabic(): bool
    {
        return app()->getLocale() === 'ar';
    }

    /**
     * @param  object|null  $row  Model or row with optional name, name_en, name_ar
     */
    public static function name(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic()) {
            return $row->name_ar ?? $row->name_en ?? $row->name;
        }

        return $row->name_en ?? $row->name;
    }

    /**
     * @param  object|null  $row  Model or row with optional description, description_en, description_ar
     */
    public static function description(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic()) {
            return $row->description_ar ?? $row->description_en ?? $row->description;
        }

        return $row->description_en ?? $row->description;
    }
}
