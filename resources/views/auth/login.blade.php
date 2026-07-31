<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name', 'Laundry Manager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="legacy-content bg-light">
    <div class="d-flex align-items-center justify-content-center min-vh-100 overflow-auto py-4">
        <div class="card shadow-sm" style="width: 420px;">
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

                @if (config('app.show_demo_accounts'))
                    <hr class="my-4">
                    <div class="small">
                        <div class="fw-bold mb-1">Demo accounts (development only)</div>
                        <div class="text-muted mb-2">Password for every seeded account: <code>ChangeMe123!</code></div>
                        <div class="list-group">
                            @foreach ([
                                ['label' => 'Administrator', 'email' => 'admin@laundrymanagement.test'],
                                ['label' => 'Manager', 'email' => 'manager@laundrymanagement.test'],
                                ['label' => 'Cashier', 'email' => 'cashier@laundrymanagement.test'],
                                ['label' => 'Laundry Staff', 'email' => 'staff@laundrymanagement.test'],
                                ['label' => 'Driver', 'email' => 'driver@laundrymanagement.test'],
                                ['label' => 'Accountant', 'email' => 'accountant@laundrymanagement.test'],
                            ] as $demo)
                                <button type="button"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-2"
                                        onclick="document.querySelector('input[name=email]').value='{{ $demo['email'] }}';document.querySelector('input[name=password]').value='ChangeMe123!';">
                                    <span>{{ $demo['label'] }}</span>
                                    <span class="text-muted">{{ $demo['email'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
