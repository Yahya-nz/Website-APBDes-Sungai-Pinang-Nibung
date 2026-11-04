<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Pencarian baris anggaran berdasarkan kata kunci.
 *
 * Setiap kata harus ditemukan (di uraian, kategori, atau sumber), lalu hasil
 * diurutkan dari yang paling relevan: kecocokan di uraian lebih penting daripada
 * kategori, dan kategori lebih penting daripada sumber.
 */
final class BudgetSearch
{
    private const WEIGHTS = ['description' => 6, 'category' => 3, 'source' => 1];

    /** Kata yang lebih pendek dari ini hanya dicocokkan dengan awal kata. */
    private const MIN_INFIX_LENGTH = 3;

    /** @var list<string> */
    private array $terms;

    private string $phrase;

    public function __construct(string $query)
    {
        $this->phrase = self::normalize($query);
        $this->terms = array_values(array_unique(array_filter(explode(' ', $this->phrase), fn (string $term): bool => $term !== '')));
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /**
     * @return list<string>
     */
    public function terms(): array
    {
        return $this->terms;
    }

    /**
     * Nilai relevansi sebuah baris, atau null jika ada kata yang tidak ditemukan.
     *
     * @param  array{description: string, category: string, source: ?string}  $fields
     */
    public function score(array $fields): ?int
    {
        $normalized = array_map(fn (?string $value): string => self::normalize((string) $value), $fields);
        $score = 0;

        foreach ($this->terms as $term) {
            $best = 0;
            foreach (self::WEIGHTS as $field => $weight) {
                $match = $this->matchStrength($term, $normalized[$field] ?? '');
                $best = max($best, $match * $weight);
            }
            if ($best === 0) {
                return null;
            }
            $score += $best;
        }

        if (count($this->terms) > 1 && str_contains($normalized['description'] ?? '', $this->phrase)) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Tandai kata kunci di dalam teks. Teks tetap di-escape sehingga aman ditampilkan.
     */
    public function highlight(?string $text): HtmlString
    {
        $text = (string) $text;
        if ($this->isEmpty() || $text === '') {
            return new HtmlString(e($text));
        }

        $patterns = array_map(function (string $term): string {
            $quoted = preg_quote($term, '/');

            return mb_strlen($term) < self::MIN_INFIX_LENGTH ? '(?<![\p{L}\p{N}])'.$quoted : $quoted;
        }, $this->terms);
        usort($patterns, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $parts = preg_split('/('.implode('|', $patterns).')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return new HtmlString(e($text));
        }

        $html = '';
        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1 ? '<mark>'.e($part).'</mark>' : e($part);
        }

        return new HtmlString($html);
    }

    /**
     * 2 = cocok di awal kata, 1 = cocok di tengah kata, 0 = tidak cocok.
     */
    private function matchStrength(string $term, string $haystack): int
    {
        if ($haystack === '') {
            return 0;
        }
        if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'/u', $haystack)) {
            return 2;
        }
        if (mb_strlen($term) >= self::MIN_INFIX_LENGTH && str_contains($haystack, $term)) {
            return 1;
        }

        return 0;
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
