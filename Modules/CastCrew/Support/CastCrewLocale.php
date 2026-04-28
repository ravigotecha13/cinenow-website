<?php

namespace Modules\CastCrew\Support;

/**
 * Localized cast/crew text. When Arabic columns are empty, falls back to EN/legacy columns.
 * No automatic translation is performed.
 */
final class CastCrewLocale
{
    public static function isArabic(): bool
    {
        return app()->getLocale() === 'ar';
    }

    public static function name(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic() && ! empty(trim((string) ($row->name_ar ?? '')))) {
            return $row->name_ar;
        }

        return $row->name_en ?? $row->name;
    }

    public static function bio(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic() && ! empty(trim((string) ($row->bio_ar ?? '')))) {
            return $row->bio_ar;
        }

        return $row->bio_en ?? $row->bio;
    }

    public static function placeOfBirth(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic() && ! empty(trim((string) ($row->place_of_birth_ar ?? '')))) {
            return $row->place_of_birth_ar;
        }

        return $row->place_of_birth_en ?? $row->place_of_birth;
    }

    public static function designation(?object $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if (self::isArabic() && ! empty(trim((string) ($row->designation_ar ?? '')))) {
            return $row->designation_ar;
        }

        return $row->designation_en ?? $row->designation;
    }
}
