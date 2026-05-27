<x-layouts.app title="{{ $profile->exists ? 'Edit profile' : 'Submit creator' }}">
    <section class="card">
        <p class="eyebrow">{{ $profile->exists ? 'Edit metadata' : 'Public submission form' }}</p>
        <h1>{{ $profile->exists ? 'Edit '.$profile->name : 'Submit a creator for review.' }}</h1>
        <p class="bio">Submissions enter the pending queue. Admin approval is required before public discovery.</p>

        @if ($suggested)
            <div class="notice">
                Suggested tags. Confirm or edit before saving:
                {{ implode(', ', array_merge($suggested['geographies'] ?? [], $suggested['topics'] ?? [], $suggested['formats'] ?? [])) }}
            </div>
        @endif

        <form class="stack" method="POST" action="{{ route('profiles.suggest-tags') }}">
            @csrf
            <label><span>Paste bio for tag suggestions</span><textarea name="bio">{{ old('bio', $profile->bio) }}</textarea></label>
            <button>Suggest tags</button>
        </form>

        <form class="stack split-form" method="POST" action="{{ $action }}">
            @csrf
            @if ($method === 'PUT')
                @method('PUT')
            @endif
            <label><span>Name</span><input name="name" value="{{ old('name', $profile->name) }}"></label>
            <label><span>Platform link</span><input name="platform_link" value="{{ old('platform_link', $profile->platform_link) }}"></label>
            <label><span>Company / organization</span><input name="company" value="{{ old('company', $profile->company) }}"></label>
            <label class="full"><span>Bio</span><textarea name="bio">{{ old('bio', $profile->bio) }}</textarea></label>

            @foreach (['geographies' => config('signal.geographies'), 'topics' => config('signal.topics'), 'formats' => config('signal.formats')] as $field => $items)
                @php $selected = old($field, $suggested[$field] ?? ($profile->{$field} ?? [])); @endphp
                <fieldset class="full checks">
                    <legend>{{ ucfirst($field) }}</legend>
                    @foreach ($items as $item)
                        <label><input type="checkbox" name="{{ $field }}[]" value="{{ $item }}" @checked(in_array($item, $selected ?? [], true))> {{ $item }}</label>
                    @endforeach
                </fieldset>
            @endforeach
            <button class="full">{{ $profile->exists ? 'Save profile' : 'Submit for review' }}</button>
        </form>
    </section>
</x-layouts.app>
