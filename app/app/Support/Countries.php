<?php

namespace App\Support;

class Countries
{
    public static function all(): array
    {
        return config('countries', []);
    }

    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (string $name, string $code) => ['code' => $code, 'name' => $name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public static function name(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::all()[$code] ?? $code;
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }
}
