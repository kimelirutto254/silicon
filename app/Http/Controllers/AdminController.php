<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DuplicateFlag;
use App\Models\Profile;
use App\Models\Recommendation;
use App\Services\AuditLogger;
use App\Services\SignalAnalysisService;
use App\Services\TrustScoringService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $this->authorizeAdmin();

        return view('admin.dashboard', [
            'pending' => Profile::where('status', 'pending')->latest()->get(),
            'duplicates' => DuplicateFlag::with(['profile', 'possibleDuplicate'])->where('status', 'open')->latest()->get(),
            'conflicts' => Recommendation::with(['profile', 'user'])->where('conflict_flagged', true)->where('conflict_overridden', false)->latest()->get(),
            'logs' => AuditLog::with('actor')->latest()->limit(30)->get(),
        ]);
    }

    public function edit(Profile $profile)
    {
        $this->authorizeAdmin();

        return view('admin.profile-edit', ['profile' => $profile]);
    }

    public function approve(Request $request, Profile $profile, TrustScoringService $scoring, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $before = $profile->toArray();
        $profile->forceFill([
            'status' => 'approved',
            'approved_by_id' => $request->user()->id,
            'approved_at' => now(),
        ])->save();
        $scoring->recalculate($profile);
        $audit->log($request->user(), 'admin.profile_approved', $profile, $before, $profile->fresh()->toArray());

        return back()->with('status', 'Profile approved.');
    }

    public function reject(Request $request, Profile $profile, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $before = $profile->toArray();
        $profile->forceFill(['status' => 'rejected'])->save();
        $audit->log($request->user(), 'admin.profile_rejected', $profile, $before, $profile->fresh()->toArray());

        return back()->with('status', 'Profile rejected.');
    }

    public function bulk(Request $request, TrustScoringService $scoring, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $validated = $request->validate([
            'profile_ids' => ['required', 'array'],
            'action' => ['required', 'in:approve,reject'],
        ]);

        Profile::whereIn('id', $validated['profile_ids'])->each(function (Profile $profile) use ($request, $validated, $scoring, $audit) {
            $before = $profile->toArray();
            $profile->forceFill([
                'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
                'approved_by_id' => $validated['action'] === 'approve' ? $request->user()->id : null,
                'approved_at' => $validated['action'] === 'approve' ? now() : null,
            ])->save();
            $scoring->recalculate($profile);
            $audit->log($request->user(), 'admin.profile_bulk_'.$validated['action'], $profile, $before, $profile->fresh()->toArray());
        });

        return back()->with('status', 'Bulk action complete.');
    }

    public function mergeDuplicate(Request $request, DuplicateFlag $duplicate, TrustScoringService $scoring, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $duplicate->load('profile', 'possibleDuplicate');
        $before = $duplicate->toArray();

        $duplicate->profile->recommendations->each(function (Recommendation $recommendation) use ($duplicate) {
            $exists = Recommendation::where('profile_id', $duplicate->possible_duplicate_id)
                ->where('user_id', $recommendation->user_id)
                ->exists();

            $exists
                ? $recommendation->delete()
                : $recommendation->forceFill(['profile_id' => $duplicate->possible_duplicate_id])->save();
        });
        $duplicate->profile->forceFill(['status' => 'rejected'])->save();
        $duplicate->forceFill(['status' => 'merged', 'resolved_by_id' => $request->user()->id, 'resolved_at' => now()])->save();
        $scoring->recalculate($duplicate->possibleDuplicate);
        $audit->log($request->user(), 'admin.duplicate_merged', $duplicate, $before, $duplicate->fresh()->toArray());

        return back()->with('status', 'Duplicate merged.');
    }

    public function dismissDuplicate(Request $request, DuplicateFlag $duplicate, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $before = $duplicate->toArray();
        $duplicate->forceFill(['status' => 'dismissed', 'resolved_by_id' => $request->user()->id, 'resolved_at' => now()])->save();
        $audit->log($request->user(), 'admin.duplicate_dismissed', $duplicate, $before, $duplicate->fresh()->toArray());

        return back()->with('status', 'Duplicate dismissed.');
    }

    public function conflict(Request $request, Recommendation $recommendation, TrustScoringService $scoring, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $validated = $request->validate(['decision' => ['required', 'in:confirm,override']]);
        $before = $recommendation->toArray();
        $recommendation->forceFill([
            'conflict_confirmed' => $validated['decision'] === 'confirm',
            'conflict_overridden' => $validated['decision'] === 'override',
        ])->save();
        $scoring->recalculate($recommendation->profile);
        $audit->log($request->user(), 'admin.conflict_'.$validated['decision'], $recommendation, $before, $recommendation->fresh()->toArray());

        return back()->with('status', 'Conflict decision saved.');
    }

    public function refreshDataQuality(Profile $profile, SignalAnalysisService $analysis, AuditLogger $audit)
    {
        $this->authorizeAdmin();
        $profile->loadCount('recommendations');
        $result = $analysis->dataQuality($profile->toArray());
        $before = $profile->toArray();
        $profile->forceFill(['data_quality_score' => $result['score'], 'data_quality_notes' => $result['notes']])->save();
        $audit->log(auth()->user(), 'admin.data_quality_scanned', $profile, $before, $profile->fresh()->toArray());

        return back()->with('status', 'Data quality scan refreshed.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }
}
