<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Congregation;

final class CongregationProfileFields
{
    public const LABELS = [
        'legacy_username' => 'Username lama',
        'legacy_role' => 'Role Firebase',
        'blood_type' => 'Golongan darah',
        'last_education' => 'Pendidikan terakhir',
        'church_origin' => 'Asal gereja',
        'reason_to_move_church' => 'Alasan pindah gereja',
        'family_status' => 'Status dalam keluarga',
        'father_name' => 'Nama ayah',
        'mother_name' => 'Nama ibu',
        'husband_name' => 'Nama suami',
        'wife_name' => 'Nama istri',
        'children_names' => 'Nama anak',
        'siblings_names' => 'Nama saudara',
        'holy_spirit_baptism' => 'Baptisan Roh Kudus',
        'baptism_church' => 'Gereja baptisan air',
    ];

    /**
     * Extract known labels without replacing existing profile values or losing unrecognized notes.
     *
     * @return array<string, mixed>
     */
    public static function extract(?string $notes, array $existing = []): array
    {
        $fields = [];
        $remaining = [];
        $columns = array_flip(self::LABELS);

        foreach (preg_split('/\R/u', $notes ?? '') ?: [] as $line) {
            [$label, $raw] = array_pad(explode(':', $line, 2), 2, null);
            $column = $columns[trim($label)] ?? null;
            $value = $column !== null && $raw !== null ? self::normalize($column, trim($raw)) : null;
            if ($column === null || $value === null) {
                $remaining[] = $line;

                continue;
            }

            $current = $fields[$column] ?? $existing[$column] ?? null;
            if (in_array($column, ['children_names', 'siblings_names'], true) && is_string($current)) {
                $current = json_decode($current, true);
            }
            if ($column === 'holy_spirit_baptism' && $current !== null) {
                $current = (bool) $current;
            }
            if ($current !== null && $current !== $value) {
                $remaining[] = $line;

                continue;
            }

            $fields[$column] = $value;
        }

        return $fields + ['notes' => trim(implode("\n", $remaining)) ?: null];
    }

    public static function forDisplay(Congregation $item): Congregation
    {
        $display = clone $item;
        $display->fill(self::extract($item->notes, $item->getAttributes()));

        return $display;
    }

    private static function normalize(string $column, string $value): mixed
    {
        if ($value === '') {
            return null;
        }
        if ($column === 'holy_spirit_baptism') {
            return match (mb_strtolower($value)) {
                '1', 'true', 'yes', 'ya', 'y', 'sudah' => true,
                '0', 'false', 'no', 'tidak', 'n', 'belum' => false,
                default => null,
            };
        }
        if (in_array($column, ['children_names', 'siblings_names'], true)) {
            $names = array_values(array_filter(array_map('trim', preg_split('/[,;]+/u', $value) ?: []), fn (string $name): bool => $name !== ''));

            return $names !== [] && collect($names)->every(fn (string $name): bool => mb_strlen($name) <= 255) ? $names : null;
        }
        if ($column === 'blood_type') {
            return in_array(strtoupper($value), ['A', 'B', 'AB', 'O'], true) ? strtoupper($value) : null;
        }
        $max = match ($column) {
            'last_education', 'family_status' => 100,
            'reason_to_move_church' => 2000,
            default => 255,
        };

        return mb_strlen($value) <= $max ? $value : null;
    }
}
