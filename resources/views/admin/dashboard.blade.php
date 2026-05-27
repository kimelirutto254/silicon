<x-layouts.app title="Admin · SX Signal Engine">
    <header class="topbar">
        <div><p class="eyebrow">Admin Panel</p><h1>Moderate integrity, duplicates, conflicts, and audit history.</h1></div>
        <div class="metrics">
            <div><strong>{{ $pending->count() }}</strong><span>Pending</span></div>
            <div><strong>{{ $duplicates->count() }}</strong><span>Duplicates</span></div>
            <div><strong>{{ $conflicts->count() }}</strong><span>Conflicts</span></div>
        </div>
    </header>

    <section class="card">
        <p class="eyebrow">Profile Approval Queue</p>
        <form id="bulk-profile-form" method="POST" action="{{ route('admin.profiles.bulk') }}">@csrf</form>
        <div class="queue">
            @forelse ($pending as $profile)
                <article class="admin-item">
                    <label><input form="bulk-profile-form" type="checkbox" name="profile_ids[]" value="{{ $profile->id }}"> <b>{{ $profile->name }}</b></label>
                    <span>{{ $profile->provenance }} · Data quality {{ $profile->data_quality_score }}/10 · pending review</span>
                    <p>{{ $profile->bio }}</p>
                    <div class="actions">
                        <a class="button-link" href="{{ route('profiles.show', $profile) }}">Show</a>
                        <a class="button-link" href="{{ route('admin.profiles.edit', $profile) }}">Edit metadata</a>
                        <form method="POST" action="{{ route('admin.profiles.approve', $profile) }}">@csrf<button>Approve</button></form>
                        <form method="POST" action="{{ route('admin.profiles.reject', $profile) }}">@csrf<button>Reject</button></form>
                        <form method="POST" action="{{ route('admin.profiles.data-quality', $profile) }}">@csrf<button>Data quality</button></form>
                    </div>
                </article>
            @empty
                <div class="empty">No pending profiles.</div>
            @endforelse
        </div>
        @if ($pending->count())
            <div class="actions"><button form="bulk-profile-form" name="action" value="approve">Bulk approve</button><button form="bulk-profile-form" name="action" value="reject">Bulk reject</button></div>
        @endif
    </section>

    <section class="card">
        <p class="eyebrow">Duplicate Alert Queue</p>
        <div class="queue">
            @forelse ($duplicates as $duplicate)
                <article class="dupe-grid">
                    <div><b>{{ $duplicate->profile->name }}</b><p>{{ $duplicate->profile->bio }}</p></div>
                    <div><b>{{ $duplicate->possibleDuplicate->name }}</b><p>{{ $duplicate->possibleDuplicate->bio }}</p></div>
                    <div>
                        <span>{{ number_format($duplicate->confidence * 100, 0) }}% confidence</span>
                        <p>{{ json_encode($duplicate->reasons) }}</p>
                        <form method="POST" action="{{ route('admin.duplicates.merge', $duplicate) }}">@csrf<button>Merge</button></form>
                        <form method="POST" action="{{ route('admin.duplicates.dismiss', $duplicate) }}">@csrf<button>Dismiss</button></form>
                    </div>
                </article>
            @empty
                <div class="empty">No duplicate alerts.</div>
            @endforelse
        </div>
    </section>

    <section class="card">
        <p class="eyebrow">Conflict Flags Queue</p>
        <div class="queue">
            @forelse ($conflicts as $recommendation)
                <article>
                    <b>{{ $recommendation->user->name }} → {{ $recommendation->profile->name }}</b>
                    <span>{{ implode(', ', $recommendation->conflict_reasons ?? []) }}</span>
                    <p>{{ $recommendation->rationale }}</p>
                    <div class="actions">
                        <form method="POST" action="{{ route('admin.conflicts.decide', $recommendation) }}">@csrf<button name="decision" value="confirm">Confirm flag</button><button name="decision" value="override">Override</button></form>
                    </div>
                </article>
            @empty
                <div class="empty">No open conflict flags.</div>
            @endforelse
        </div>
    </section>

    <section class="card">
        <p class="eyebrow">Full Audit Log</p>
        <div class="queue">
            @foreach ($logs as $log)
                <article>
                    <b>{{ $log->action }}</b>
                    <span>{{ $log->actor?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</span>
                    <p>{{ class_basename($log->target_type) }} #{{ $log->target_id }}</p>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
