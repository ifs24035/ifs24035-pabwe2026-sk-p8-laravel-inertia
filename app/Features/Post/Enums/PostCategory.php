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
     * Human readable label (with description) of the category.
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
     * Tailwind badge classes used by the frontend.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::TEKNOLOGI => 'bg-blue-100 text-blue-800 border-blue-300',
            self::PENDIDIKAN => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::GAYA_HIDUP => 'bg-pink-100 text-pink-800 border-pink-300',
            self::BISNIS => 'bg-amber-100 text-amber-800 border-amber-300',
            self::OPINI => 'bg-purple-100 text-purple-800 border-purple-300',
            self::UMUM => 'bg-slate-100 text-slate-800 border-slate-300',
        };
    }

    /**
     * All backing values of the enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Options array shared with the frontend (select boxes, filters, badges).
     *
     * @return array<int, array{value: string, label: string, badgeClass: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'badgeClass' => $case->badgeClass(),
        ], self::cases());
    }
}
