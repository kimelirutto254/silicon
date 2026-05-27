<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Recommendation;
use Carbon\CarbonImmutable;

class TrustScoringService
{
    public function recalculate(Profile $profile): Profile
    {
        $profile->load('recommendations');

        $score = 0;
        $roles = [];

        foreach ($profile->recommendations as $recommendation) {
            $weight = $this->roleWeight($recommendation->role_badge);
            $decay = $this->decay($recommendation);
            $penalty = $recommendation->conflict_flagged && ! $recommendation->conflict_overridden ? 0.55 : 1;
            $verified = $recommendation->verified_identity ? 1.12 : 0.88;
            $contribution = 10 * $weight * $decay * $penalty * $verified;

            $recommendation->forceFill([
                'weight' => round($weight, 3),
                'decay_factor' => round($decay, 3),
            ])->saveQuietly();

            $score += $contribution;
            $roles[] = $recommendation->role_badge;
        }

        $score += count(array_unique($roles)) * 2.5;
        $score += min(count($profile->geographies ?? []), 3) * 1.25;

        $count = $profile->recommendations->count();
        $confidence = match (true) {
            $count >= 5 => 'High',
            $count >= 3 => 'Medium',
            default => 'Low',
        };

        $profile->forceFill([
            'trust_score' => round($score, 1),
            'confidence_level' => $confidence,
        ])->saveQuietly();

        return $profile->refresh();
    }

    public function explain(Profile $profile): array
    {
        $profile->load('recommendations.user');

        return [
            'profile' => $profile,
            'total' => $profile->trust_score,
            'confidence' => $profile->confidence_level,
            'recommendations' => $profile->recommendations->map(function (Recommendation $recommendation) {
                $conflictPenalty = $recommendation->conflict_flagged && ! $recommendation->conflict_overridden ? 0.55 : 1;

                return [
                    'recommender' => $recommendation->user->name,
                    'role' => $recommendation->role_badge,
                    'role_weight' => $recommendation->weight,
                    'decay_factor' => $recommendation->decay_factor,
                    'verified_identity' => $recommendation->verified_identity,
                    'conflict_status' => $recommendation->conflict_flagged ? ($recommendation->conflict_overridden ? 'overridden' : 'flagged') : 'clear',
                    'conflict_reasons' => $recommendation->conflict_reasons ?? [],
                    'contribution' => round(10 * $recommendation->weight * $recommendation->decay_factor * $conflictPenalty * ($recommendation->verified_identity ? 1.12 : 0.88), 2),
                    'rationale' => $recommendation->rationale,
                ];
            })->all(),
        ];
    }

    private function roleWeight(string $role): float
    {
        return (float) data_get(config('signal.roles'), "{$role}.weight", 0.7);
    }

    private function decay(Recommendation $recommendation): float
    {
        $days = CarbonImmutable::parse($recommendation->created_at)->diffInDays(now());

        return max(0.55, exp(-$days / 365));
    }
}
