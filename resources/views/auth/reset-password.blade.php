<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Buat password baru untuk akun CatatIn.">
    <title>Atur Ulang Password - CatatIn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        try {
            const saved = JSON.parse(localStorage.getItem('catatin-ui-settings') || '{}');
            if (/^#[\da-f]{6}$/i.test(saved.accent || '')) {
                const channels = saved.accent.slice(1).match(/[\da-f]{2}/gi).map((channel) => parseInt(channel, 16));
                const hover = channels.map((channel) => Math.round(channel * .78));
                document.documentElement.style.setProperty('--theme-accent', saved.accent);
                document.documentElement.style.setProperty('--theme-accent-hover', `rgb(${hover.join(', ')})`);
            }
        } catch {}
    </script>
    <style>
        :root {
            --theme-accent: #2F6F64;
            --theme-accent-hover: #24574F;
        }
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #1d302d; background: #f3f5f2; background-image: radial-gradient(ellipse at 12% 18%, rgba(47,111,100,.10), transparent 34rem), linear-gradient(145deg, #f8faf8, #e9efeb); font-family: "Segoe UI", Arial, sans-serif; }
        .auth-card { position: relative; width: min(100%, 500px); padding: 42px; overflow: hidden; border: 1px solid rgba(36,75,69,.08); border-radius: 24px; background: linear-gradient(155deg, #fff, #f9fbf9); box-shadow: 0 32px 90px rgba(29,48,45,.12); }
        .auth-card::before { position: absolute; top: 0; right: 0; left: 0; height: 7px; content: ""; background: linear-gradient(90deg, var(--theme-accent-hover), var(--theme-accent), #9bb9a7); }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 34px; color: var(--theme-accent); font-size: 21px; font-weight: 750; letter-spacing: -.03em; }
        .brand::before { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 14px; color: #fff; content: "C"; background: linear-gradient(145deg, var(--theme-accent-hover), var(--theme-accent)); }
        .brand-tagline { color: #81918a; font-size: 10px; font-weight: 700; letter-spacing: .16em; }
        h1 { font-size: 28px; font-weight: 700; letter-spacing: -.04em; }
        .intro { margin: 8px 0 28px; color: #71817d; font-size: 14px; line-height: 1.7; }
        .form-label { color: #344742; font-size: 13px; font-weight: 650; }
        .form-control { min-height: 49px; border-color: #dce5e0; border-radius: 11px; }
        .form-control:focus { border-color: var(--theme-accent); box-shadow: 0 0 0 .2rem rgba(47,111,100,.15); }
        .submit-button { min-height: 49px; border: 0; border-radius: 11px; color: #fff; background: var(--theme-accent); font-weight: 650; transition: background-color .15s ease; }
        .submit-button:hover { color: #fff; background: var(--theme-accent-hover); }
        .back-link { color: var(--theme-accent); font-size: 13px; font-weight: 600; text-decoration: none; }
        .back-link:hover { color: var(--theme-accent-hover); text-decoration: underline; }
        .alert { border-radius: 9px; font-size: 13px; }
        @media (max-width: 480px) { body { padding: 16px 12px; } .auth-card { padding: 34px 24px 28px; border-radius: 20px; } }
    </style>
</head>
<body>
    <main class="auth-card">
        <div class="brand">CatatIn <span class="brand-tagline">ACCOUNT ACCESS</span></div>
        <h1>Buat password baru</h1>
        <p class="intro">Password baru minimal 8 karakter. Kombinasi huruf, angka, atau simbol bebas.</p>
        <p class="intro">Setelah berhasil disimpan, password baru ini akan menggantikan password lama dan digunakan untuk login berikutnya.</p>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update', ['token' => $token]) }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label for="email" class="form-label">Email akun</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password baru</label>
                <input id="password" type="password" name="password" minlength="8" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Ulangi password baru</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" class="form-control" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn submit-button w-100">Simpan password baru</button>
        </form>

        <div class="text-center mt-4">
            <a class="back-link" href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </div>
    </main>
</body>
</html>
