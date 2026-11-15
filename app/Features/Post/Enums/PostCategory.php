<?php

namespace App\Features\Post\Enums;

enum PostCategory: string
{
    case TEKNOLOGI = 'Teknologi';
    case PENDIDIKAN = 'Pendidikan';
    case GAYA_HIDUP = 'Gaya Hidup';
    case BISNIS = 'Bisnis';
    case OPINI = 'Opini';
    case UMUM = 'Umum';

    /**
     * Human readable label shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::TEKNOLOGI => 'Teknologi & Koding',
            self::PENDIDIKAN => 'Pendidikan & Tutorial',
            self::GAYA_HIDUP => 'Gaya Hidup & Produktivitas',
            self::BISNIS => 'Bisnis & Karir',
            self::OPINI => 'Opini & Pemikiran',
            self::UMUM => 'Umum',
        };
    }

    /**
     * Tailwind classes for the category badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::TEKNOLOGI => 'bg-blue-100 text-blue-800',
            self::PENDIDIKAN => 'bg-emerald-100 text-emerald-800',
            self::GAYA_HIDUP => 'bg-pink-100 text-pink-800',
            self::BISNIS => 'bg-amber-100 text-amber-800',
            self::OPINI => 'bg-purple-100 text-purple-800',
            self::UMUM => 'bg-slate-200 text-slate-800',
        };
    }

    /**
     * All raw values, used by validation rules.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Options for select inputs in the frontend.
     *
     * @return list<array{value: string, label: string, badgeClass: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'badgeClass' => $case->badgeClass(),
        ], self::cases());
    }

    /**
     * Resolve a category from loose input (value or label, any case).
     * Falls back to UMUM so imported / AI data is always valid.
     */
    public static function fromLoose(?string $value): self
    {
        $needle = mb_strtolower(trim((string) $value));

        foreach (self::cases() as $case) {
            if ($needle === mb_strtolower($case->value) || $needle === mb_strtolower($case->label())) {
                return $case;
            }
        }

        return self::UMUM;
    }
}
