<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'noteBoard') · noteBoard</title>
    @vite('resources/js/app.js')
</head>
<body class="bg-body-tertiary">
@auth
    <nav class="navbar navbar-expand-md bg-body border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">noteBoard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav"
                    aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Pages</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="{{ route('account.edit') }}">{{ auth()->user()->name }}</a></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link">Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
@else
    <div class="container py-4"><span class="fs-4 fw-semibold">noteBoard</span></div>
@endauth
<main class="container pb-5">
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
