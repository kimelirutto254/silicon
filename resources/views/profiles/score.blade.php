<x-layouts.app title="Score explainer · {{ $profile->name }}">
    <section class="card">
        <p class="eyebrow">Score Explainer Endpoint</p>
        <h1>Why {{ $profile->name }} scores {{ number_format($total, 1) }}</h1>
        <p class="bio">Transparent breakdown of role weight, freshness decay, verification, and conflict status.</p>
        <div class="score-grid">
            <div><strong>{{ number_format($total, 1) }}</strong><span>Total</span></div>
            <div><strong>{{ $confidence }}</strong><span>Confidence</span></div>
            <div><strong>{{ count($recommendations) }}</strong><span>Inputs</span></div>
            <div><strong>{{ collect($recommendations)->where('conflict_status', 'flagged')->count() }}</strong><span>Conflicts</span></div>
        </div>
    </section>
    <section class="table-panel">
        <div class="table-head explainer-head"><span>Recommender</span><span>Role</span><span>Decay</span><span>Conflict</span><span>Contribution</span></div>
        @foreach ($recommendations as $row)
            <div class="row explainer-row">
                <span><b>{{ $row['recommender'] }}</b><small>{{ $row['rationale'] }}</small></span>
                <span>{{ $row['role'] }} × {{ $row['role_weight'] }}</span>
                <span>{{ $row['decay_factor'] }}</span>
                <span>{{ $row['conflict_status'] }} {{ $row['conflict_reasons'] ? '('.implode(', ', $row['conflict_reasons']).')' : '' }}</span>
                <strong>{{ $row['contribution'] }}</strong>
            </div>
        @endforeach
    </section>
</x-layouts.app>
