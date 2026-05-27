<?php

namespace Database\Seeders;

use App\Models\EcosystemPulse;
use App\Models\Profile;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\IntegrityService;
use App\Services\SeedCreatorProfileCatalog;
use App\Services\SignalAnalysisService;
use App\Services\TrustScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $analysis = app(SignalAnalysisService::class);
        $scoring = app(TrustScoringService::class);
        $integrity = app(IntegrityService::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@siliconxchange.tech'],
            [
                'name' => 'SX Admin',
                'password' => 'password',
                'professional_role' => 'Operator',
                'company' => 'Silicon Xchange',
                'is_admin' => true,
                'identity_verified' => true,
            ]
        );

        $users = collect([
            ['Amina Diallo', 'amina@example.com', 'Investor', 'Savannah Valley Ventures'],
            ['Tomiwa Adeyemi', 'tomiwa@example.com', 'Founder', 'MarketForge'],
            ['Nandi Mokoena', 'nandi@example.com', 'Operator', 'Cape Scale Studio'],
            ['Brian Mwangi', 'brian@example.com', 'Researcher', 'Nairobi Data Lab'],
            ['Esi Mensah', 'esi@example.com', 'Journalist', 'Accra Tech Desk'],
            ['Karim Hassan', 'karim@example.com', 'Policy', 'Digital Policy Forum'],
        ])->mapWithKeys(fn (array $row) => [
            $row[0] => User::updateOrCreate(['email' => $row[1]], [
                'name' => $row[0],
                'password' => 'password',
                'professional_role' => $row[2],
                'company' => $row[3],
                'identity_verified' => true,
            ]),
        ]);

        foreach (app(SeedCreatorProfileCatalog::class)->all() as $item) {
            $profile = Profile::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'bio' => $item['bio'],
                    'platform_link' => $item['link'],
                    'geographies' => $item['geography'],
                    'topics' => $this->normalize($item['topics'], config('signal.topics')),
                    'formats' => $item['formats'],
                    'status' => $item['status'] === 'Approved' ? 'approved' : ($item['status'] === 'Duplicate' ? 'pending' : 'pending'),
                    'provenance' => 'LinkedIn thread',
                    'submitted_by_id' => $admin->id,
                    'approved_by_id' => $item['status'] === 'Approved' ? $admin->id : null,
                    'approved_at' => $item['status'] === 'Approved' ? now() : null,
                    'company' => str_contains($item['name'], 'TechCabal') ? 'TechCabal' : null,
                    'search_vector' => $analysis->searchVector($item['name'].' '.$item['bio'].' '.implode(' ', $item['topics'])),
                ]
            );

            foreach ($item['recommendations'] as $rec) {
                $user = $users[$rec['name']] ?? $admin;
                Recommendation::updateOrCreate(
                    ['profile_id' => $profile->id, 'user_id' => $user->id],
                    [
                        'rationale' => $rec['snippet'],
                        'role_badge' => $user->professional_role,
                        'company' => $user->company,
                        'verified_identity' => $rec['verified'],
                        'created_at' => $rec['date'],
                        'updated_at' => $rec['date'],
                    ]
                );
            }

            $profile->loadCount('recommendations');
            $complete = $analysis->dataQuality($profile->toArray());
            $profile->forceFill([
                'data_quality_score' => $complete['score'],
                'data_quality_notes' => $complete['notes'],
            ])->saveQuietly();

            foreach ($profile->recommendations as $recommendation) {
                $integrity->inspectRecommendation($recommendation);
            }

            $profile = $scoring->recalculate($profile);
            if ($profile->recommendations()->count() >= 3) {
                $profile->forceFill([
                    'credibility_summary' => $analysis->credibilityBrief($profile->name, $profile->recommendations()->pluck('rationale')->all()),
                    'summary_generated_at' => now(),
                ])->saveQuietly();
            }
        }

        Profile::where('slug', 'tech-cabal')->first()?->forceFill(['status' => 'pending'])->saveQuietly();
        Profile::where('slug', 'techcabal')->first()?->forceFill(['status' => 'approved'])->saveQuietly();
        Profile::where('status', 'pending')->get()->each(fn (Profile $profile) => $integrity->detectDuplicates($profile));

        EcosystemPulse::updateOrCreate(['week_starts_at' => now()->startOfWeek()->toDateString()], [
            'headline' => 'Africa tech media graph is live',
            'summary' => 'The first ranked corpus is seeded with funding, fintech, policy, operator, and venture voices. Trust scores are active and pending submissions are waiting for admin review.',
            'metrics' => ['new_profiles' => Profile::count(), 'new_recommendations' => Recommendation::count(), 'top_profile' => Profile::orderByDesc('trust_score')->value('name')],
        ]);
    }

    private function normalize(array $topics, array $allowed): array
    {
        $map = [
            'Market Intel' => 'Data',
            'Reports' => 'Funding',
            'Directories' => 'Startups',
            'Training' => 'Founders',
            'Gender' => 'Founders',
            'Capital' => 'Venture',
            'Strategy' => 'Venture',
            'Scaling' => 'Founders',
            'Markets' => 'Business',
            'Operators' => 'Product',
            'Tools' => 'Product',
            'Awards' => 'Startups',
            'MENA' => 'Business',
            'Labor' => 'Policy',
            'Platforms' => 'Infrastructure',
            'Investors' => 'Investing',
        ];

        return array_values(array_unique(array_filter(array_map(fn (string $topic) => in_array($topic, $allowed, true) ? $topic : ($map[$topic] ?? null), $topics))));
    }
}
