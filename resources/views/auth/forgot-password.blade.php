<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - {{ config('app.name', 'Laundry Manager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="card shadow-sm" style="width: 380px;">
            <div class="card-body p-4">
                <h4 class="mb-4 text-center">Reset Password</h4>

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

                <p class="text-muted small">Enter your email address and we'll send you a password reset link if an account exists.</p>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="small">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
