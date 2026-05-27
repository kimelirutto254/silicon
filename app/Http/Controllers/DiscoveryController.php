<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\EcosystemPulseService;
use App\Services\IntegrityService;
use App\Services\SignalAnalysisService;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    public function index(Request $request, EcosystemPulseService $pulse, SignalAnalysisService $analysis, IntegrityService $integrity)
    {
        $profiles = Profile::withCount('recommendations')
            ->with(['recommendations.user'])
            ->where('status', 'approved')
            ->get();

        $query = trim($request->string('q')->toString());
        $expanded = $request->boolean('expanded') || str($query)->contains(['who ', 'covers', 'find ', 'voices']);

        if ($expanded && $query) {
            $queryVector = $analysis->searchVector($query);
            $profiles = $profiles
                ->map(function (Profile $profile) use ($integrity, $queryVector) {
                    $profile->search_similarity = $integrity->cosine($queryVector, $profile->search_vector ?? []);

                    return $profile;
                })
                ->filter(fn (Profile $profile) => $profile->search_similarity > 0.08)
                ->sortByDesc(fn (Profile $profile) => ($profile->search_similarity * 100) + $profile->trust_score);
        } else {
            $profiles = $this->keywordFilter($profiles, $query);
        }

        $profiles = $this->facetFilter($profiles, $request);
        $profiles = $this->sort($profiles, $request->string('sort', 'trust')->toString());

        return view('discovery.index', [
            'profiles' => $profiles->values(),
            'pulse' => $pulse->current(),
            'filters' => [
                'q' => $query,
                'geo' => $request->string('geo', 'All')->toString(),
                'topic' => $request->string('topic', 'All')->toString(),
                'format' => $request->string('format', 'All')->toString(),
                'sort' => $request->string('sort', 'trust')->toString(),
                'expanded' => $expanded,
            ],
            'options' => [
                'geographies' => config('signal.geographies'),
                'topics' => config('signal.topics'),
                'formats' => config('signal.formats'),
            ],
        ]);
    }

    private function keywordFilter($profiles, string $query)
    {
        if (! $query) {
            return $profiles;
        }

        $query = strtolower($query);

        return $profiles->filter(function (Profile $profile) use ($query) {
            $blob = strtolower(implode(' ', [
                $profile->name,
                $profile->bio,
                implode(' ', $profile->geographies ?? []),
                implode(' ', $profile->topics ?? []),
                implode(' ', $profile->formats ?? []),
                $profile->recommendations->pluck('rationale')->implode(' '),
            ]));

            return str_contains($blob, $query);
        });
    }

    private function facetFilter($profiles, Request $request)
    {
        foreach (['geo' => 'geographies', 'topic' => 'topics', 'format' => 'formats'] as $param => $field) {
            $value = $request->string($param, 'All')->toString();
            if ($value !== 'All') {
                $profiles = $profiles->filter(fn (Profile $profile) => in_array($value, $profile->{$field} ?? [], true));
            }
        }

        return $profiles;
    }

    private function sort($profiles, string $sort)
    {
        return match ($sort) {
            'recent' => $profiles->sortByDesc('updated_at'),
            'recommended' => $profiles->sortByDesc('recommendations_count'),
            default => $profiles->sortByDesc('trust_score'),
        };
    }
}
