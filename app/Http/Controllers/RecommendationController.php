<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Recommendation;
use App\Services\AuditLogger;
use App\Services\IntegrityService;
use App\Services\SignalAnalysisService;
use App\Services\TrustScoringService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function store(Request $request, Profile $profile, IntegrityService $integrity, TrustScoringService $scoring, SignalAnalysisService $analysis, AuditLogger $audit)
    {
        abort_unless($profile->status === 'approved', 403);

        $validated = $request->validate([
            'rationale' => ['required', 'string', 'min:30', 'max:700'],
        ]);

        $recommendation = Recommendation::updateOrCreate(
            ['profile_id' => $profile->id, 'user_id' => $request->user()->id],
            [
                'rationale' => $validated['rationale'],
                'role_badge' => $request->user()->professional_role,
                'company' => $request->user()->company,
                'verified_identity' => $request->user()->identity_verified,
            ]
        );

        $integrity->inspectRecommendation($recommendation);
        $profile = $scoring->recalculate($profile);

        if ($profile->recommendations()->count() >= 3) {
            $profile->forceFill([
                'credibility_summary' => $analysis->credibilityBrief($profile->name, $profile->recommendations()->pluck('rationale')->all()),
                'summary_generated_at' => now(),
            ])->saveQuietly();
        }

        $audit->log($request->user(), 'recommendation.saved', $recommendation, [], $recommendation->fresh()->toArray());

        return back()->with('status', 'Recommendation saved and trust score recalculated.');
    }
}
