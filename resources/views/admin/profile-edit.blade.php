<x-layouts.app title="Admin edit · {{ $profile->name }}">
    <section class="card">
        <p class="eyebrow">Admin metadata edit</p>
        <h1>{{ $profile->name }}</h1>
        <p class="bio">Use the standard edit form to preserve validation and controlled vocabulary.</p>
        <div class="actions">
            <a class="button-link" href="{{ route('profiles.edit', $profile) }}">Open edit form</a>
            <form method="POST" action="{{ route('admin.profiles.approve', $profile) }}">@csrf<button>Approve</button></form>
            <form method="POST" action="{{ route('admin.profiles.reject', $profile) }}">@csrf<button>Reject</button></form>
        </div>
    </section>
</x-layouts.app>
