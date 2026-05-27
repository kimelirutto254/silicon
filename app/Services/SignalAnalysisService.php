<?php

namespace App\Services;

class SignalAnalysisService
{
    public function suggestTags(string $bio): array
    {
        $haystack = strtolower($bio);

        return [
            'geographies' => $this->pick($haystack, config('signal.geographies'), ['pan-african' => 'Pan-African', 'kenya' => 'Kenya', 'nigeria' => 'Nigeria']),
            'topics' => $this->pick($haystack, config('signal.topics'), ['funding' => 'Funding', 'venture' => 'Venture', 'fintech' => 'Fintech', 'policy' => 'Policy', 'startup' => 'Startups']),
            'formats' => $this->pick($haystack, config('signal.formats'), ['podcast' => 'Podcast', 'newsletter' => 'Newsletter', 'research' => 'Research', 'publication' => 'Publication', 'linkedin' => 'LinkedIn']),
        ];
    }

    public function credibilityBrief(string $name, array $snippets): string
    {
        if (count($snippets) < 3) {
            return '';
        }

        $themes = $this->keywords(implode(' ', $snippets), 5);
        $themeText = $themes ? implode(', ', $themes) : 'ecosystem context';

        return "{$name} is repeatedly recommended for {$themeText}. The strongest signal is that multiple recommenders describe concrete value, not just general popularity.";
    }

    public function dataQuality(array $profile): array
    {
        $notes = [];
        $score = 10;

        foreach (['bio', 'platform_link'] as $field) {
            if (blank($profile[$field] ?? null)) {
                $score -= 2;
                $notes[] = "Missing {$field}.";
            }
        }

        foreach (['geographies', 'topics', 'formats'] as $field) {
            if (empty($profile[$field] ?? [])) {
                $score -= 1;
                $notes[] = "Needs {$field}.";
            }
        }

        if (($profile['recommendations_count'] ?? 0) < 2) {
            $score -= 2;
            $notes[] = 'Needs 2 more recommendations to be featured.';
        }

        return [
            'score' => max(1, $score),
            'notes' => $notes ?: ['Profile has enough metadata for review.'],
        ];
    }

    public function pulse(array $metrics): array
    {
        return [
            'headline' => 'Weekly signal is ready',
            'summary' => "{$metrics['new_profiles']} new voices added, {$metrics['new_recommendations']} recommendations captured, and {$metrics['top_profile']} is the most recommended source this week.",
        ];
    }

    public function searchVector(string $text): array
    {
        $vector = array_fill(0, 32, 0.0);
        $tokens = preg_split('/[^a-z0-9]+/', strtolower($text)) ?: [];

        foreach ($tokens as $token) {
            if (strlen($token) < 3) {
                continue;
            }

            $vector[crc32($token) % 32] += 1;
        }

        $norm = sqrt(array_sum(array_map(fn (float $value) => $value * $value, $vector))) ?: 1;

        return array_map(fn (float $value) => round($value / $norm, 6), $vector);
    }

    private function pick(string $haystack, array $vocabulary, array $defaults): array
    {
        $picked = [];

        foreach ($vocabulary as $item) {
            if (str_contains($haystack, strtolower($item))) {
                $picked[] = $item;
            }
        }

        foreach ($defaults as $needle => $item) {
            if (str_contains($haystack, $needle)) {
                $picked[] = $item;
            }
        }

        return array_values(array_unique($picked ?: [array_values($defaults)[0] ?? $vocabulary[0]]));
    }

    private function keywords(string $text, int $limit): array
    {
        $stop = ['about', 'across', 'after', 'again', 'because', 'before', 'context', 'their', 'there', 'these', 'those', 'useful', 'with', 'when', 'where'];
        $counts = [];

        foreach (preg_split('/[^a-z0-9]+/', strtolower($text)) ?: [] as $word) {
            if (strlen($word) < 5 || in_array($word, $stop, true)) {
                continue;
            }

            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, $limit);
    }
}
