<x-layouts.app title="SX Signal Engine">
    <header class="topbar">
        <div>
            <p class="eyebrow">Discovery Feed</p>
            <h1>Credible Africa tech voices, ranked by accountable signal.</h1>
        </div>
        <div class="metrics" aria-label="Dataset summary">
            <div><strong>{{ $profiles->count() }}</strong><span>Visible</span></div>
            <div><strong>{{ $profiles->sum('recommendations_count') }}</strong><span>Recs</span></div>
            <div><strong>{{ $filters['expanded'] ? 'On' : 'Off' }}</strong><span>Expanded</span></div>
        </div>
    </header>

    <section class="pulse">
        <div>
            <p class="eyebrow">Weekly Signal</p>
            <h2>{{ $pulse->headline }}</h2>
            <p>{{ $pulse->summary }}</p>
        </div>
    </section>

    <form class="filters" method="GET" action="{{ route('discovery.index') }}">
        <label class="search"><span>Search</span><input name="q" value="{{ $filters['q'] }}" placeholder="Search names, regions, topics, or formats"></label>
        <label><span>Geography</span><select name="geo"><option>All</option>@foreach ($options['geographies'] as $option)<option @selected($filters['geo'] === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>Topic</span><select name="topic"><option>All</option>@foreach ($options['topics'] as $option)<option @selected($filters['topic'] === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>Format</span><select name="format"><option>All</option>@foreach ($options['formats'] as $option)<option @selected($filters['format'] === $option)>{{ $option }}</option>@endforeach</select></label>
        <label><span>Sort</span><select name="sort"><option value="trust" @selected($filters['sort'] === 'trust')>Trust score</option><option value="recent" @selected($filters['sort'] === 'recent')>Most recent</option><option value="recommended" @selected($filters['sort'] === 'recommended')>Most recommended</option></select></label>
        <label class="check"><input type="checkbox" name="expanded" value="1" @checked($filters['expanded'])> Expanded</label>
        <button type="submit">Apply</button>
    </form>

    @guest
        <div class="notice">Login is required to recommend sources or submit creators. Public browsing stays open; anonymous actions do not.</div>
    @endguest

    <section class="table-panel">
        <div class="panel-head">
            <span>Ranked discovery</span>
            <span>{{ $profiles->count() }} matches</span>
        </div>
        <div class="table-head feed-head">
            <span>Score</span><span>Source</span><span>Signal</span><span>Tags</span><span>Status</span>
        </div>
        <div class="rows">
            @forelse ($profiles as $profile)
                <a class="row feed-row" href="{{ route('profiles.show', $profile) }}">
                    <strong class="score">{{ number_format($profile->trust_score, 1) }}</strong>
                    <span>
                        <b>{{ $profile->name }}</b>
                        <small>{{ $profile->credibility_summary ?: $profile->bio }}</small>
                    </span>
                    <span class="meta-stack">
                        <em>{{ $profile->recommendations_count }} recs</em>
                        <em>{{ $profile->confidence_level }} confidence</em>
                        <em>{{ $profile->provenance }}</em>
                        @if (auth()->user()?->is_admin && $profile->recommendations->contains('conflict_flagged', true))
                            <em class="warn">Conflict</em>
                        @endif
                    </span>
                    <span class="tags">
                        @foreach (array_slice(array_merge($profile->geographies, $profile->topics, $profile->formats), 0, 5) as $tag)
                            <em>{{ $tag }}</em>
                        @endforeach
                    </span>
                    <span class="status">{{ ucfirst($profile->status) }}</span>
                </a>
            @empty
                <div class="empty">No voices match this query yet. Try a broader search or submit a new creator after login.</div>
            @endforelse
        </div>
    </section>
</x-layouts.app>
