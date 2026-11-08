<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Minta tautan untuk mengatur ulang password akun CatatIn.">
    <title>Lupa Password - CatatIn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #1d302d; background: #edf2ef; font-family: "Segoe UI", Arial, sans-serif; }
        .auth-card { width: min(100%, 460px); padding: 36px; border: 1px solid #e5ebe8; border-radius: 16px; background: #fff; box-shadow: 0 20px 60px rgba(29,48,45,.12); }
        .brand { margin-bottom: 26px; color: #24574f; font-size: 19px; font-weight: 700; }
        h1 { font-size: 25px; font-weight: 700; }
        .intro { margin: 8px 0 24px; color: #71817d; font-size: 14px; line-height: 1.6; }
        .form-label { color: #344742; font-size: 13px; font-weight: 650; }
        .form-control { min-height: 46px; border-color: #dce5e0; border-radius: 8px; }
        .submit-button { min-height: 46px; border: 0; border-radius: 8px; color: #fff; background: #24574f; font-weight: 650; }
        .submit-button:hover { color: #fff; background: #2f6f64; }
        .back-link { color: #24574f; font-size: 13px; text-decoration: none; }
        .alert { font-size: 13px; }
        @media (max-width: 480px) { .auth-card { padding: 28px 22px; } }
    </style>
</head>
<body>
    <main class="auth-card">
        <div class="brand">CatatIn</div>
        <h1>Lupa password?</h1>
        <p class="intro">Masukkan email akun Anda. Jika terdaftar, permintaan tautan untuk membuat password baru akan diproses.</p>

        @if(in_array(config('mail.default'), ['log', 'array'], true))
            <div class="alert alert-warning" role="status">
                @if(config('mail.default') === 'log')
                    Email tidak dikirim karena aplikasi sedang menggunakan mode <strong>log</strong>. Untuk pengujian lokal, tautan reset password bagi alamat terdaftar dapat ditemukan di <code>storage/logs/laravel.log</code>. Buka tautan sebelum token kedaluwarsa ({{ config('auth.passwords.users.expire') }} menit). Jangan gunakan mode ini sebagai kanal reset password di production.
                @else
                    Email tidak dikirim. Dalam mode <strong>array</strong>, pesan reset hanya tersedia sementara selama aplikasi berjalan dan tidak dapat dibuka dari inbox.
                @endif
            </div>
        @endif
        @if(session('status') && !in_array(config('mail.default'), ['log', 'array'], true))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-4">
                <label for="email" class="form-label">Email akun</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn submit-button w-100">Kirim tautan reset</button>
        </form>

        <div class="text-center mt-4">
            <a class="back-link" href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </div>
    </main>
</body>
</html>
