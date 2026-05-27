<?php

namespace App\Services;

use App\Models\EcosystemPulse;
use App\Models\Profile;
use App\Models\Recommendation;
use Carbon\CarbonImmutable;

class EcosystemPulseService
{
    public function __construct(private SignalAnalysisService $analysis) {}

    public function current(): EcosystemPulse
    {
        $week = CarbonImmutable::now()->startOfWeek();

        return EcosystemPulse::whereDate('week_starts_at', $week)->first()
            ?? EcosystemPulse::create(['week_starts_at' => $week->toDateString(), ...$this->buildPayload($week)]);
    }

    public function regenerate(): EcosystemPulse
    {
        $week = CarbonImmutable::now()->startOfWeek();
        $payload = $this->buildPayload($week);

        return tap(EcosystemPulse::updateOrCreate(['week_starts_at' => $week->toDateString()], $payload));
    }

    private function buildPayload(CarbonImmutable $week): array
    {
        $newProfiles = Profile::where('created_at', '>=', $week)->count();
        $newRecommendations = Recommendation::where('created_at', '>=', $week)->count();
        $topProfile = Profile::query()->orderByDesc('trust_score')->value('name') ?? 'No approved profile yet';
        $metrics = [
            'new_profiles' => $newProfiles,
            'new_recommendations' => $newRecommendations,
            'top_profile' => $topProfile,
        ];
        $copy = $this->analysis->pulse($metrics);

        return [
            'headline' => $copy['headline'],
            'summary' => $copy['summary'],
            'metrics' => $metrics,
        ];
    }
}
