<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class SignalRanker
{
    private const ROLE_WEIGHTS = [
        'Founder' => 1.20,
        'Investor' => 1.15,
        'Operator' => 1.10,
        'Researcher' => 1.00,
        'Journalist' => 0.95,
        'Policy' => 0.90,
    ];

    public function rank(array $profiles): array
    {
        $ranked = array_map(fn (array $profile) => $this->score($profile), $profiles);

        usort($ranked, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return $ranked;
    }

    public function score(array $profile): array
    {
        $recommendations = $profile['recommendations'];
        $roles = array_map(fn (array $rec) => $rec['role'], $recommendations);
        $roleAverage = count($roles)
            ? array_sum(array_map(fn (string $role) => self::ROLE_WEIGHTS[$role] ?? 1, $roles)) / count($roles)
            : 0;

        $base = count($recommendations) * 10;
        $verified = count(array_filter($recommendations, fn (array $rec) => $rec['verified'])) * 4;
        $role = $roleAverage * 10;
        $diversity = count(array_unique($roles)) * 3 + min(count($profile['geography']), 3) * 1.5;
        $freshness = $this->freshness($profile['last_seen']) * 12;
        $duplicatePenalty = $profile['status'] === 'Duplicate' ? -25 : 0;

        $profile['score_parts'] = [
            'base' => round($base, 1),
            'verified' => round($verified, 1),
            'role' => round($role, 1),
            'diversity' => round($diversity, 1),
            'freshness' => round($freshness, 1),
            'duplicate_penalty' => round($duplicatePenalty, 1),
        ];
        $profile['score'] = round($base + $verified + $role + $diversity + $freshness + $duplicatePenalty, 1);

        return $profile;
    }

    private function freshness(string $lastSeen): float
    {
        $days = CarbonImmutable::parse($lastSeen)->diffInDays(CarbonImmutable::parse('2026-05-20'));

        return max(0.62, exp(-$days / 365));
    }
}
