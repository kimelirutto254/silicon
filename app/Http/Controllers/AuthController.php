<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function redirect()
    {
        return Socialite::driver('linkedin-openid')->redirect();
    }

    public function callback(AuditLogger $audit)
    {
        try {
            $linkedin = Socialite::driver('linkedin-openid')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['linkedin' => 'LinkedIn OAuth is not configured yet. Add LinkedIn credentials or use local demo login.']);
        }

        $user = User::updateOrCreate(
            ['linkedin_id' => $linkedin->getId()],
            [
                'name' => $linkedin->getName() ?: $linkedin->getNickname() ?: 'LinkedIn Member',
                'email' => $linkedin->getEmail() ?: Str::uuid().'@linkedin.local',
                'avatar_url' => $linkedin->getAvatar(),
                'password' => Str::password(32),
                'identity_verified' => true,
            ]
        );

        Auth::login($user, true);
        $audit->log($user, 'auth.linkedin_login', $user);

        return redirect()->intended($user->needsRoleSelection() ? route('role.edit') : route('discovery.index'));
    }

    public function demo(Request $request, AuditLogger $audit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'professional_role' => ['required', 'string'],
            'company' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'password' => Str::password(32),
                'identity_verified' => true,
            ]
        );

        $user->forceFill([
            'name' => $validated['name'],
            'professional_role' => $validated['professional_role'],
            'company' => $validated['company'] ?? null,
        ])->save();

        Auth::login($user, true);
        $audit->log($user, 'auth.demo_login', $user);

        return redirect()->route('discovery.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('discovery.index');
    }
}
