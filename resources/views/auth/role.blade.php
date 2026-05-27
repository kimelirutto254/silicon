<x-layouts.app title="Select role · SX Signal Engine">
    <section class="card narrow">
        <p class="eyebrow">First login</p>
        <h1>Select your professional role.</h1>
        <p class="bio">This role becomes the badge and weight on future recommendations. It can be audited by admins.</p>
        <form class="stack" method="POST" action="{{ route('role.update') }}">
            @csrf
            <label><span>Professional role</span><select name="professional_role">@foreach ($roles as $role)<option>{{ $role }}</option>@endforeach</select></label>
            <label><span>Company</span><input name="company" placeholder="Company, fund, studio, or publication"></label>
            <button>Save role</button>
        </form>
    </section>
</x-layouts.app>
