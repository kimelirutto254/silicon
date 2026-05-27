<x-layouts.app title="Login · SX Signal Engine" :hide-nav="true" shell-class="shell-login">
    <section class="auth-shell">
        <div class="auth-story">
            <p class="eyebrow inverted">Silicon Xchange</p>
            <h1>Build a trusted map of Africa tech signal.</h1>
            <p>Every recommendation is tied to a professional identity, weighted by trust, and reviewed for integrity.</p>
            <div class="auth-feature-list">
                <span><b>↗</b> Ranked ecosystem discovery</span>
                <span><b>✓</b> Verified professional vouching</span>
                <span><b>◎</b> Guided metadata quality</span>
            </div>
            <div class="trusted-strip">
                <span>SX</span>
                <span>VC</span>
                <span>OP</span>
                <small>Built for founders, investors, operators, and ecosystem researchers.</small>
            </div>
        </div>
        <div class="auth-card">
            <div class="auth-card-head">
                <span class="brand-mark large">SX</span>
                <div>
                    <p class="eyebrow">Welcome back</p>
                    <h2>Sign in to continue</h2>
                </div>
            </div>
            <a class="button-link primary wide" href="{{ route('linkedin.redirect') }}">Continue with LinkedIn</a>
            <form class="stack" method="POST" action="{{ route('login.demo') }}">
                @csrf
                <label><span>Name</span><input name="name" value="Dismas W."></label>
                <label><span>Email</span><input name="email" type="email" value="dismas@example.com"></label>
                <label><span>Professional role</span><select name="professional_role">@foreach (array_keys(config('signal.roles')) as $role)<option>{{ $role }}</option>@endforeach</select></label>
                <label><span>Company</span><input name="company" value="Savannah Valley Ventures"></label>
                <button class="primary">Enter demo app</button>
            </form>
        </div>
    </section>
</x-layouts.app>
