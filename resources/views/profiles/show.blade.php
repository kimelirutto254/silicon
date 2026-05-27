<x-layouts.app title="{{ $profile->name }} · SX Signal Engine">
    <section class="layout">
        <div class="card">
            <p class="eyebrow">{{ $profile->provenance }} · {{ ucfirst($profile->status) }}</p>
            <div class="profile-title">
                <div>
                    <h1>{{ $profile->name }}</h1>
                    <p class="bio">{{ $profile->bio }}</p>
                </div>
                <a class="icon-link" href="{{ $profile->platform_link }}" target="_blank" rel="noreferrer">↗</a>
            </div>
            <div class="score-grid">
                <div><strong>{{ number_format($profile->trust_score, 1) }}</strong><span>Trust score</span></div>
                <div><strong>{{ $profile->recommendations->count() }}</strong><span>Recommendations</span></div>
                <div><strong>{{ $profile->confidence_level }}</strong><span>Confidence</span></div>
                <div><strong>{{ $profile->data_quality_score }}/10</strong><span>Data quality</span></div>
            </div>
            @if ($profile->credibility_summary)
                <div class="pulse"><p class="eyebrow">Credibility Brief</p><p>{{ $profile->credibility_summary }}</p></div>
            @endif
            <div class="tags loose">
                @foreach (array_merge($profile->geographies, $profile->topics, $profile->formats) as $tag)
                    <em>{{ $tag }}</em>
                @endforeach
            </div>
            <div class="actions">
                <a class="button-link" href="{{ route('profiles.score', $profile) }}">Why this score?</a>
                @auth
                    <a class="button-link" href="{{ route('profiles.edit', $profile) }}">Edit</a>
                @endauth
            </div>
        </div>

        <aside class="side">
            <section class="card">
                <p class="eyebrow">Recommendation System</p>
                <h2>Add a recommendation</h2>
                @auth
                    <form class="stack" method="POST" action="{{ route('recommendations.store', $profile) }}">
                        @csrf
                        <p class="bio">Your badge: {{ auth()->user()->professional_role }} · {{ auth()->user()->company ?: 'No company set' }}</p>
                        <label><span>Mandatory rationale</span><textarea name="rationale" placeholder="Why is this source worth following?"></textarea></label>
                        <button>Submit recommendation</button>
                    </form>
                @else
                    <div class="empty">Login is required. Anonymous recommendations are blocked.</div>
                @endauth
            </section>
        </aside>
    </section>

    <section class="layout lower">
        <div class="card">
            <p class="eyebrow">All recommendation snippets</p>
            <div class="snippets">
                @forelse ($profile->recommendations as $recommendation)
                    <article>
                        <header>
                            <b>{{ $recommendation->user->name }}</b>
                            <span class="role role-{{ str($recommendation->role_badge)->slug() }}">{{ $recommendation->role_badge }}</span>
                        </header>
                        <p>{{ $recommendation->rationale }}</p>
                        @if (auth()->user()?->is_admin && $recommendation->conflict_flagged)
                            <small class="warn">Admin conflict flag: {{ implode(', ', $recommendation->conflict_reasons ?? []) }}</small>
                        @endif
                    </article>
                @empty
                    <div class="empty">No recommendations yet.</div>
                @endforelse
            </div>
        </div>

        <aside class="card">
            <p class="eyebrow">Similar Voices</p>
            <h2>If you follow {{ $profile->name }}, consider</h2>
            <div class="queue">
                @foreach ($similar as $row)
                    <article>
                        <b><a href="{{ route('profiles.show', $row['profile']) }}">{{ $row['profile']->name }}</a></b>
                        <span>{{ number_format($row['similarity'] * 100, 0) }}% similar · {{ number_format($row['profile']->trust_score, 1) }} trust</span>
                    </article>
                @endforeach
            </div>
        </aside>
    </section>
</x-layouts.app>
