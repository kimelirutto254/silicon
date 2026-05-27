<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\TrustScoringService;

class ScoreController extends Controller
{
    public function show(Profile $profile, TrustScoringService $scoring)
    {
        abort_unless($profile->status === 'approved' || auth()->user()?->is_admin, 404);

        return view('profiles.score', $scoring->explain($profile));
    }
}
