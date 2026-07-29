<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name', 'Laundry Manager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="card shadow-sm" style="width: 380px;">
            <div class="card-body p-4">
                <h4 class="mb-4 text-center">Laundry Manager</h4>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
