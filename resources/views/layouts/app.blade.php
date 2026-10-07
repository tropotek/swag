<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Swag') · Swag</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script>
        (function () {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    @vite('resources/js/app.js')
</head>
<body class="bg-body-tertiary">
@auth
    <nav class="navbar navbar-expand-md bg-body border-bottom mb-4 d-print-none">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">🗞️ Swag</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav"
                    aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Pages</a></li>
                </ul>
                <ul class="navbar-nav align-items-md-center">
                    <li class="nav-item me-md-2 mb-2 mb-md-0">
                        <form method="GET" action="{{ route('home') }}" role="search">
                            <label for="nav-search" class="visually-hidden">Search pages</label>
                            <div class="input-group input-group-sm">
                                <input type="search" id="nav-search" name="q" value="{{ request()->routeIs('home') ? \App\Models\Page::searchTerm(request()->query('q')) : '' }}"
                                       class="form-control" placeholder="Search pages" maxlength="200">
                                <button type="submit" class="btn btn-outline-secondary" aria-label="Search">🔍</button>
                            </div>
                        </form>
                    </li>
                    <li class="nav-item">
                        <button type="button" id="theme-toggle" class="nav-link btn btn-link" aria-label="Toggle light/dark theme">🌙</button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="account-menu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-md-end" aria-labelledby="account-menu">
                            <li><a class="dropdown-item" href="{{ route('account.edit') }}">Account</a></li>
                            @if (auth()->user()->is_admin)
                                <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">Users</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('tokens.index') }}">API tokens</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Log out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
@else
    <div class="container py-4"><span class="fs-4 fw-semibold">🗞️ Swag</span></div>
@endauth
<main class="container pb-5">
    @if (session('status'))
        <div class="alert alert-success d-print-none">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-print-none">{{ session('error') }}</div>
    @endif
    @yield('content')
</main>
<footer class="container d-print-none border-top mt-4 py-3 text-body-secondary small d-flex justify-content-end gap-2">
    <a href="https://github.com/tropotek/swag" target="_blank" rel="noopener" class="link-secondary text-decoration-none">GitHub</a>
    <span aria-hidden="true">|</span>
    <a href="https://tropotek.github.io/swag/" target="_blank" rel="noopener" class="link-secondary text-decoration-none">Docs</a>
</footer>
</body>
</html>
