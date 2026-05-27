@props([
    'title' => 'SX Signal Engine',
    'hideNav' => false,
    'shellClass' => '',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/signal.css') }}">
</head>
<body>
    <main class="shell {{ $shellClass }}">
        @unless ($hideNav)
            <nav class="nav">
                <a href="{{ route('discovery.index') }}" class="brand">
                    <span class="brand-mark">SX</span>
                    <span>Signal Engine</span>
                </a>
                <div class="nav-actions">
                    <a class="nav-link" href="{{ route('profiles.create') }}">Submit creator</a>
                    @auth
                        @if (auth()->user()->is_admin)
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>
                        @endif
                        <span class="identity-pill">{{ auth()->user()->name }} · {{ auth()->user()->professional_role ?: 'Role needed' }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="ghost-button">Logout</button>
                        </form>
                    @else
                        <a class="button-link compact" href="{{ route('login') }}">Login</a>
                    @endif
                </div>
            </nav>
        @endunless

        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice bad">{{ $errors->first() }}</div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
