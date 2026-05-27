<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\AuditLogger;
use App\Services\IntegrityService;
use App\Services\SignalAnalysisService;
use App\Services\TrustScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Profile $profile, IntegrityService $integrity, TrustScoringService $scoring)
    {
        abort_unless($profile->status === 'approved' || auth()->user()?->is_admin || auth()->id() === $profile->submitted_by_id, 404);
        $profile->load('recommendations.user');

        return view('profiles.show', [
            'profile' => $profile,
            'similar' => $integrity->similarVoices($profile),
            'score' => $scoring->explain($profile),
        ]);
    }

    public function create()
    {
        return view('profiles.form', [
            'profile' => new Profile,
            'action' => route('profiles.store'),
            'method' => 'POST',
            'suggested' => session('suggested_tags'),
        ]);
    }

    public function store(Request $request, SignalAnalysisService $analysis, IntegrityService $integrity, AuditLogger $audit)
    {
        $validated = $this->validated($request);
        $profile = Profile::create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(5)),
            'status' => 'pending',
            'provenance' => 'Direct submission',
            'submitted_by_id' => $request->user()->id,
            'search_vector' => $analysis->searchVector($validated['name'].' '.$validated['bio']),
        ]);

        $this->refreshDataQuality($profile, $analysis);
        $integrity->detectDuplicates($profile);
        $audit->log($request->user(), 'profile.submitted', $profile, [], $profile->toArray());

        return redirect()->route('profiles.show', $profile)->with('status', 'Profile submitted for admin review.');
    }

    public function edit(Profile $profile)
    {
        abort_unless(auth()->user()?->is_admin || auth()->id() === $profile->submitted_by_id, 403);

        return view('profiles.form', [
            'profile' => $profile,
            'action' => route('profiles.update', $profile),
            'method' => 'PUT',
            'suggested' => session('suggested_tags'),
        ]);
    }

    public function update(Request $request, Profile $profile, SignalAnalysisService $analysis, IntegrityService $integrity, AuditLogger $audit)
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $profile->submitted_by_id, 403);
        $before = $profile->toArray();
        $validated = $this->validated($request);
        $profile->update([
            ...$validated,
            'search_vector' => $analysis->searchVector($validated['name'].' '.$validated['bio']),
            'status' => $request->user()->is_admin ? $profile->status : 'pending',
        ]);

        $this->refreshDataQuality($profile, $analysis);
        $integrity->detectDuplicates($profile);
        $audit->log($request->user(), 'profile.updated', $profile, $before, $profile->fresh()->toArray());

        return redirect()->route('profiles.show', $profile)->with('status', 'Profile updated.');
    }

    public function suggestTags(Request $request, SignalAnalysisService $analysis)
    {
        $validated = $request->validate(['bio' => ['required', 'string', 'min:20']]);

        return back()->withInput()->with('suggested_tags', $analysis->suggestTags($validated['bio']));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'bio' => ['required', 'string', 'min:40'],
            'platform_link' => ['required', 'url'],
            'company' => ['nullable', 'string', 'max:120'],
            'geographies' => ['required', 'array', 'min:1'],
            'geographies.*' => [Rule::in(config('signal.geographies'))],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => [Rule::in(config('signal.topics'))],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => [Rule::in(config('signal.formats'))],
        ]);
    }

    private function refreshDataQuality(Profile $profile, SignalAnalysisService $analysis): void
    {
        $profile->loadCount('recommendations');
        $result = $analysis->dataQuality($profile->toArray());
        $profile->forceFill([
            'data_quality_score' => $result['score'],
            'data_quality_notes' => $result['notes'],
        ])->saveQuietly();
    }
}
