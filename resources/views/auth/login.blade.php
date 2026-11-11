<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Masuk atau daftar di CatatIn, ruang untuk menyimpan dan memahami materi belajar.">
    <title>Masuk - CatatIn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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
            --pine: var(--theme-accent-hover);
            --teal: var(--theme-accent);
            --teal-dark: var(--theme-accent-hover);
            --ink: #1d302d;
            --muted: #71817d;
            --line: #e5ebe8;
            --paper: #fbfcfa;
        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px;
            color: var(--ink);
            background-color: #f3f5f2;
            background-image: radial-gradient(ellipse at 12% 18%, rgba(47,111,100,.10), transparent 34rem), linear-gradient(145deg, #f8faf8, #e9efeb);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .auth-card {
            width: min(100%, 1080px);
            display: grid;
            grid-template-columns: .88fr 1.12fr;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(36,75,69,.08);
            border-radius: 28px;
            box-shadow: 0 32px 90px rgba(29,48,45,.12);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 680px;
            padding: 52px;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 82% 18%, rgba(255,255,255,.14), transparent 16rem),
                linear-gradient(148deg, var(--theme-accent-hover) 0%, var(--theme-accent) 68%, var(--theme-accent-light, #568276) 100%);
        }

        .brand-panel::after {
            position: absolute;
            right: -80px;
            bottom: 76px;
            width: 430px;
            height: 1px;
            content: "";
            transform: rotate(-42deg);
            background: rgba(255,255,255,.18);
            box-shadow: 0 28px 0 rgba(255,255,255,.08), 0 56px 0 rgba(255,255,255,.06);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 12px;
            color: #fff;
            font-size: 19px;
            font-weight: 700;
        }

        .brand-name { font-size: 21px; font-weight: 700; letter-spacing: -.03em; }
        .brand-copy { position: relative; z-index: 1; max-width: 360px; }
        .brand-copy .eyebrow { color: #d3e2dc; font-size: 10px; font-weight: 750; letter-spacing: 1.8px; }
        .brand-copy h1 { margin: 14px 0; font-size: clamp(34px, 3.5vw, 46px); line-height: 1.08; font-weight: 650; letter-spacing: -.04em; }
        .brand-copy p, .brand-foot { color: rgba(255,255,255,.76); font-size: 14px; line-height: 1.7; }
        .brand-foot { position: relative; z-index: 1; margin: 0; font-size: 12px; }

        .form-panel { display: flex; flex-direction: column; justify-content: center; padding: 54px 60px; background: var(--paper); }
        .form-panel h2 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -.04em; }
        .form-intro { margin: 8px 0 28px; color: var(--muted); font-size: 14px; }

        .google-button {
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 1px solid #d6dfdb;
            border-radius: 12px;
            color: var(--ink);
            background: #fff;
            font-size: 14px;
            font-weight: 650;
            text-decoration: none;
            transition: border-color .18s ease, background-color .18s ease, transform .18s ease;
        }

        .google-button:hover { border-color: #a7bbb3; color: var(--pine); background: #f7faf8; transform: translateY(-1px); }
        .google-button .bi-google { color: #4285f4; font-size: 17px; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; color: #87958f; font-size: 11px; }
        .divider::before, .divider::after { height: 1px; flex: 1; content: ""; background: var(--line); }
        .auth-tabs { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; margin-bottom: 22px; border-radius: 12px; background: #e9efeb; }
        .auth-tabs .nav-link { min-height: 40px; border-radius: 9px; color: #64746e; font-size: 13px; font-weight: 650; }
        .auth-tabs .nav-link.active { color: var(--pine); background: #fff; box-shadow: 0 1px 3px rgba(29,48,45,.1); }
        .form-label { margin-bottom: 6px; color: #344742; font-size: 12px; font-weight: 650; }
        .form-control { min-height: 48px; border-color: #dce5e0; border-radius: 11px; font-size: 14px; }
        .form-control:focus { border-color: #6b9b8e; box-shadow: 0 0 0 .2rem rgba(57,118,107,.13); }
        .password-toggle { min-width: 46px; border-color: #dce5e0; color: #64746e; background: #fff; }
        .password-toggle:hover, .password-toggle:focus { border-color: #6b9b8e; color: var(--pine); background: #f7faf8; }
        .form-check-input:checked { border-color: var(--teal); background-color: var(--teal); }
        .form-check-label { color: #64746e; font-size: 12px; }
        .submit-button { min-height: 49px; border: 0; border-radius: 11px; color: #fff; background: var(--pine); font-size: 14px; font-weight: 650; }
        .submit-button:hover { color: #fff; background: var(--teal-dark); }
        .new-user-note { margin: 17px 0 0; padding: 12px 13px; border-left: 3px solid #c18b4a; border-radius: 0 7px 7px 0; color: #66746f; background: #f5f4ee; font-size: 12px; line-height: 1.55; }
        .alert { border-radius: 8px; font-size: 13px; }
        .login-required-page-modal { position: fixed; inset: 0; z-index: 1050; display: grid; place-items: center; padding: 18px; background: rgba(15,35,31,.56); }
        .login-required-page-dialog { width: min(100%, 430px); padding: 26px; border: 1px solid var(--line); border-radius: 13px; background: #fff; box-shadow: 0 24px 70px rgba(13,35,29,.25); }
        .login-required-page-dialog .brand-mark { color: #fff; background: var(--theme-accent); }
        .login-required-page-dialog h2 { font-size: 20px; }
        .login-required-page-dialog p { margin: 8px 0 0; color: var(--muted); font-size: 14px; line-height: 1.6; }

        @media (max-width: 767.98px) {
            body { padding: 16px 12px; }
            .auth-card { max-width: 540px; grid-template-columns: 1fr; border-radius: 22px; }
            .brand-panel { min-height: auto; gap: 24px; padding: 24px 26px; }
            .brand-copy h1 { margin: 9px 0 6px; font-size: 26px; }
            .brand-copy p, .brand-foot { display: none; }
            .brand-copy .eyebrow { font-size: 10px; }
            .brand-mark { width: 38px; height: 38px; }
            .form-panel { padding: 30px 26px 34px; }
            .form-panel h2 { font-size: 24px; }
        }
    </style>
</head>
<body>
    @php($showRegister = old('_form') === 'register')
    <main class="auth-card">
        <aside class="brand-panel">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-mark" aria-hidden="true">C</div>
                <span class="brand-name">CatatIn</span>
            </div>
            <div class="brand-copy">
                <span class="eyebrow">RUANG BELAJAR PRIBADI</span>
                <h1>Satu tempat untuk<br>semua progresmu.</h1>
                <p>Mulai dari mengatur tugas hingga mencapai target, semua perjalanan produktivitasmu lebih terarah bersama CatatIn.</p>
            </div>
            <p class="brand-foot">Catat, Pahami, Raih Prestasi.</p>
        </aside>

        <section class="form-panel">
            <h2>Selamat datang</h2>
            <p class="form-intro">Masuk ke akun CatatIn untuk melanjutkan belajar.</p>

            @if(session('success'))
                <div class="alert alert-success py-2" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2" role="alert">Periksa kembali data yang Anda masukkan.</div>
            @endif
            @if(($showLoginRequiredModal ?? false) && ! $errors->any() && ! session('success'))
                <div id="login-required-page-modal" class="login-required-page-modal" role="dialog" aria-modal="true" aria-labelledby="login-required-page-title">
                    <div class="login-required-page-dialog">
                        <div class="brand-mark mb-3" aria-hidden="true">C</div>
                        <h2 id="login-required-page-title">Login Diperlukan</h2>
                        <p>Silakan login terlebih dahulu untuk menggunakan fitur ini dan menyimpan materi pembelajaran Anda.</p>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button id="login-required-page-later" type="button" class="btn btn-light">Nanti</button>
                            <button id="login-required-page-now" type="button" class="btn submit-button px-3">Login Sekarang</button>
                        </div>
                    </div>
                </div>
            @endif

            <ul class="nav auth-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $showRegister ? '' : 'active' }} w-100" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-pane" type="button" role="tab" aria-controls="login-pane" aria-selected="{{ $showRegister ? 'false' : 'true' }}">Masuk</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $showRegister ? 'active' : '' }} w-100" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-pane" type="button" role="tab" aria-controls="register-pane" aria-selected="{{ $showRegister ? 'true' : 'false' }}">Buat akun</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $showRegister ? '' : 'show active' }}" id="login-pane" role="tabpanel" aria-labelledby="login-tab" tabindex="0">
                    <form method="POST" action="{{ route('login.store') }}" autocomplete="on">
                        @csrf
                        <input type="hidden" name="_form" value="login">
                        <div class="mb-3">
                            <label for="login-email" class="form-label">Email</label>
                            <input id="login-email" type="email" name="email" value="" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required>
                            @if(old('_form') === 'login')
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <div class="input-group">
                                <input id="login-password" type="password" name="password" value="" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                                <button class="btn password-toggle" type="button" data-password-toggle="login-password" aria-label="Tampilkan password" aria-pressed="false">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            @if(old('_form') === 'login')
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="login-remember" @checked(old('remember'))>
                                <label class="form-check-label" for="login-remember">Ingat saya</label>
                            </div>
                            <a class="small text-decoration-none" href="{{ route('password.request') }}" style="color: var(--pine);">Lupa password?</a>
                        </div>
                        <button type="submit" class="btn submit-button w-100">Masuk ke CatatIn</button>
                    </form>
                </div>

                <div class="tab-pane fade {{ $showRegister ? 'show active' : '' }}" id="register-pane" role="tabpanel" aria-labelledby="register-tab" tabindex="0">
                    <form method="POST" action="{{ route('register.store') }}" autocomplete="on">
                        @csrf
                        <input type="hidden" name="_form" value="register">
                        <div class="mb-3">
                            <label for="register-name" class="form-label">Nama</label>
                            <input id="register-name" type="text" name="name" value="{{ old('_form') === 'register' ? old('name') : '' }}" class="form-control @error('name') is-invalid @enderror" autocomplete="name" required>
                            @if(old('_form') === 'register')
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="register-email" class="form-label">Email</label>
                            <input id="register-email" type="email" name="email" value="{{ old('_form') === 'register' ? old('email') : '' }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email" required>
                            @if(old('_form') === 'register')
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="register-password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input id="register-password" type="password" name="password" minlength="8" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                                    <button class="btn password-toggle" type="button" data-password-toggle="register-password" aria-label="Tampilkan password" aria-pressed="false">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                @if(old('_form') === 'register')
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="register-password-confirmation" class="form-label">Ulangi password</label>
                                <div class="input-group">
                                    <input id="register-password-confirmation" type="password" name="password_confirmation" minlength="8" class="form-control" autocomplete="new-password" required>
                                    <button class="btn password-toggle" type="button" data-password-toggle="register-password-confirmation" aria-label="Tampilkan password" aria-pressed="false">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="password-requirements" class="mb-3 small" aria-live="polite">
                            <div class="text-muted">Password minimal 8 karakter. Kombinasi huruf, angka, atau simbol bebas.</div>
                            <ul class="mt-1 mb-0 ps-3 text-muted">
                                <li data-password-rule="confirmation">Konfirmasi password harus sama</li>
                            </ul>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="register-remember" @checked(old('remember'))>
                            <label class="form-check-label" for="register-remember">Ingat saya di perangkat ini</label>
                        </div>
                        <button type="submit" class="btn submit-button w-100">Buat akun</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.dataset.passwordToggle);
                    if (!input) return;

                    const visible = input.type === 'password';
                    input.type = visible ? 'text' : 'password';
                    button.setAttribute('aria-pressed', String(visible));
                    button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
                    button.querySelector('i')?.classList.toggle('bi-eye', !visible);
                    button.querySelector('i')?.classList.toggle('bi-eye-slash', visible);
                    input.focus();
                });
            });

            const password = document.getElementById('register-password');
            const confirmation = document.getElementById('register-password-confirmation');
            const requirements = document.getElementById('password-requirements');
            if (!password || !confirmation || !requirements) return;

            const setRule = (name, valid) => {
                const item = requirements.querySelector(`[data-password-rule="${name}"]`);
                if (!item) return;
                item.style.color = valid ? '#198754' : '';
                item.style.textDecoration = valid ? 'line-through' : '';
            };

            const updatePasswordRequirements = () => {
                const value = password.value;
                setRule('confirmation', value.length > 0 && confirmation.value === value);
            };

            password.addEventListener('input', updatePasswordRequirements);
            confirmation.addEventListener('input', updatePasswordRequirements);
            updatePasswordRequirements();
        })();
    </script>
    @if(($showLoginRequiredModal ?? false) && ! $errors->any() && ! session('success'))
        <script>
            const loginRequiredModal = document.getElementById('login-required-page-modal');
            document.getElementById('login-required-page-now')?.addEventListener('click', () => {
                loginRequiredModal?.remove();
                document.getElementById('login-email')?.focus();
            });
            document.getElementById('login-required-page-later')?.addEventListener('click', () => {
                let destination = '/';
                try {
                    const lastPage = sessionStorage.getItem('catatin-last-page');
                    if (lastPage?.startsWith('/') && !lastPage.startsWith('//') && !lastPage.includes('\\') && !lastPage.startsWith('/login')) {
                        destination = lastPage;
                    }
                } catch {}
                try {
                    const previous = new URL(document.referrer);
                    if (destination === '/' && previous.origin === window.location.origin && previous.pathname !== '/login') {
                        destination = previous.pathname + previous.search;
                    }
                } catch {}
                window.location.assign(destination);
            });
        </script>
    @endif
</body>
</html>