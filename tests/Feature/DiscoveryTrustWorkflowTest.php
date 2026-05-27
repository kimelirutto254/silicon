<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTrustWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_discovery_page_renders_ranked_profiles(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Credible Africa tech voices')
            ->assertSee('Victor Asemota')
            ->assertSee('Ranked discovery');
    }

    public function test_discovery_can_filter_by_keyword(): void
    {
        $this->get('/?q=fintech')
            ->assertOk()
            ->assertSee('Fintech')
            ->assertSee('Michael Kimani');
    }

    public function test_logged_in_user_can_submit_recommendation_and_score_updates(): void
    {
        $user = User::where('email', 'tomiwa@example.com')->first();
        $profile = Profile::where('status', 'approved')->where('name', '!=', 'TechCabal')->first();

        $this->actingAs($user)->post(route('recommendations.store', $profile), [
            'rationale' => 'Strong recurring signal on fintech policy, funding context, and operator-level market movement.',
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('recommendations', [
            'profile_id' => $profile->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_submit_profile_and_admin_can_approve_it(): void
    {
        $user = User::where('email', 'tomiwa@example.com')->first();
        $admin = User::where('email', 'admin@siliconxchange.tech')->first();

        $this->actingAs($user)->post(route('profiles.store'), [
            'name' => 'Signal Test Weekly',
            'bio' => 'A practical weekly publication covering African fintech operators, policy shifts, and funding market structure.',
            'platform_link' => 'https://example.com/signal-test',
            'company' => 'Signal Test',
            'geographies' => ['Pan-African'],
            'topics' => ['Fintech', 'Policy'],
            'formats' => ['Newsletter'],
        ])->assertRedirect();

        $profile = Profile::where('name', 'Signal Test Weekly')->firstOrFail();
        $this->assertSame('pending', $profile->status);

        $this->actingAs($admin)->post(route('admin.profiles.approve', $profile))->assertRedirect();
        $this->assertSame('approved', $profile->fresh()->status);
    }

    public function test_score_explainer_renders_breakdown(): void
    {
        $profile = Profile::where('status', 'approved')->whereHas('recommendations')->first();

        $this->get(route('profiles.score', $profile))
            ->assertOk()
            ->assertSee('Score Explainer Endpoint')
            ->assertSee('Contribution');
    }
}
