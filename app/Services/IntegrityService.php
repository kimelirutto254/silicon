<?php

namespace App\Services;

use App\Models\DuplicateFlag;
use App\Models\Profile;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IntegrityService
{
    public function __construct(private SignalAnalysisService $analysis) {}

    public function inspectRecommendation(Recommendation $recommendation): Recommendation
    {
        $recommendation->load('profile.submitter', 'user.recommendations.profile');
        $reasons = [];

        if ($recommendation->company && $recommendation->profile->company && strcasecmp($recommendation->company, $recommendation->profile->company) === 0) {
            $reasons[] = 'same company';
        }

        $targetSubmitter = $recommendation->profile->submitter;
        if ($targetSubmitter && $this->hasRecommendedProfileForUser($targetSubmitter, $recommendation->user)) {
            $reasons[] = 'reciprocal recommendation';
        }

        $recommendation->forceFill([
            'conflict_flagged' => count($reasons) > 0,
            'conflict_reasons' => $reasons,
        ])->save();

        return $recommendation;
    }

    public function detectDuplicates(Profile $profile): Collection
    {
        $profile->search_vector = $profile->search_vector ?: $this->analysis->searchVector($profile->name.' '.$profile->bio);
        $profile->saveQuietly();

        return Profile::query()
            ->whereKeyNot($profile->id)
            ->get()
            ->map(function (Profile $candidate) use ($profile) {
                similar_text(Str::lower($profile->name), Str::lower($candidate->name), $nameSimilarity);
                $bioSimilarity = $this->cosine($profile->search_vector ?? [], $candidate->search_vector ?? []);
                $confidence = max($nameSimilarity / 100, $bioSimilarity);

                return compact('candidate', 'confidence', 'nameSimilarity', 'bioSimilarity');
            })
            ->filter(fn (array $match) => $match['confidence'] >= 0.62)
            ->sortByDesc('confidence')
            ->values()
            ->each(function (array $match) use ($profile) {
                DuplicateFlag::firstOrCreate([
                    'profile_id' => $profile->id,
                    'possible_duplicate_id' => $match['candidate']->id,
                ], [
                    'confidence' => round($match['confidence'], 3),
                    'reasons' => [
                        'name_similarity' => round($match['nameSimilarity'], 1),
                        'bio_similarity' => round($match['bioSimilarity'], 3),
                    ],
                ]);
            });
    }

    public function similarVoices(Profile $profile, int $limit = 3): Collection
    {
        return Profile::query()
            ->where('status', 'approved')
            ->whereKeyNot($profile->id)
            ->get()
            ->map(fn (Profile $candidate) => [
                'profile' => $candidate,
                'similarity' => $this->cosine($profile->search_vector ?? [], $candidate->search_vector ?? []),
            ])
            ->sortByDesc(fn (array $row) => $row['similarity'] + ($row['profile']->trust_score / 200))
            ->take($limit)
            ->values();
    }

    private function hasRecommendedProfileForUser(User $userA, User $userB): bool
    {
        return $userA->recommendations()
            ->whereHas('profile', fn ($query) => $query->where('submitted_by_id', $userB->id))
            ->exists();
    }

    public function cosine(array $a, array $b): float
    {
        if (! $a || ! $b) {
            return 0;
        }

        $dot = $normA = $normB = 0.0;
        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        return $normA && $normB ? round($dot / (sqrt($normA) * sqrt($normB)), 4) : 0;
    }
}
